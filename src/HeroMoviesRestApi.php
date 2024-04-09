<?php

namespace HeroMovies;

class HeroMoviesRestApi {
	public static function register_endpoints() {
			add_action('rest_api_init', [__CLASS__, 'register']);
	}

	public static function register() {
			register_rest_route('herothemes/v1', '/login', array(
					'methods' => 'POST',
					'callback' => [__CLASS__, 'login_user'],
			));

			register_rest_route('herothemes/v1', '/verify-token', array(
					'methods' => 'GET',
					'callback' => [__CLASS__, 'verify_user_token'],
			));

			register_rest_route('herothemes/v1', '/movies', array(
					'methods' => 'GET',
					'callback' => [__CLASS__, 'get_movies'],
					'permission_callback' => function ($request) {
							return self::verify_token();
					}
			));

			register_rest_route('herothemes/v1', '/movies/(?P<id>\d+)', array(
					'methods' => 'GET',
					'callback' => [__CLASS__, 'get_movie'],
					'permission_callback' => function ($request) {
							return self::verify_token();
					}
			));
	}

	public static function login_user($request) {
			$credentials = $request->get_json_params();

			$user = wp_authenticate($credentials['username'], $credentials['password']);
			if (is_wp_error($user)) {
					return new \WP_Error('message', __('Invalid username or password.', 'text-domain'), array('status' => 401));
			}

			$token = wp_generate_password(64, false);

			$expiration_time = time() + (1 * HOUR_IN_SECONDS); 
			update_user_meta($user->ID, 'auth_token', $token);
			update_user_meta($user->ID, 'auth_token_expiration', $expiration_time);

			$user_details = array(
					'user_id' => $user->ID,
					'username' => $user->user_login,
					'email' => $user->user_email
			);

			return array(
					'success' => 1,
					'token' => $token,
					'expires_in' => 3600,
					'user' => $user_details,
					'message' => __('Login successful.', 'text-domain')
			);
	}

	public static function verify_token() {
			$token = isset($_SERVER['HTTP_AUTHORIZATION']) ? str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']) : '';

			if (empty($token)) {
					return new \WP_Error('token_missing', __('Token is missing.', 'text-domain'), array('status' => 401));
			}

			global $wpdb;

			$user_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'auth_token' AND meta_value = %s",
					$token
			));

			if ($user_id) {
					$expiration_time = get_user_meta($user_id, 'auth_token_expiration', true);
					if ($expiration_time && $expiration_time > time()) {
							wp_set_current_user($user_id);
							return true;
					} else {
							return new \WP_Error('message', __('Token has expired.', 'text-domain'), array('status' => 401));
					}
			}

			return new \WP_Error('message', __('Invalid token.', 'text-domain'), array('status' => 401));
	}

	public static function verify_user_token($request) {
			$valid = self::verify_token();

			if (is_wp_error($valid)) {
					return $valid; 
			}

			return array(
					'success' => 1,
					'message' => __('Token is valid.', 'text-domain')
			);
	}

	public static function get_movies($request) {
			$params = $request->get_params();
			$paged = isset($params['paged']) ? intval($params['paged']) : 1; 
			$posts_per_page = isset($params['posts_per_page']) ? intval($params['posts_per_page']) : 10;

			$args = array(
					'post_type'      => 'movies',
					'posts_per_page' => $posts_per_page,
					'paged'          => $paged,
			);

			$movies_query = new \WP_Query($args);
			$movies = array();

			if ($movies_query->have_posts()) {
					while ($movies_query->have_posts()) {
							$movies_query->the_post();
							$movies[] = array(
									'id'          => get_the_ID(),
									'title'       => get_the_title(),
									'description' => get_post_meta(get_the_ID(), 'movie_description', true),
									'year'        => get_post_meta(get_the_ID(), 'year_released', true)
							);
					}
			} 
			wp_reset_postdata();

			$total_movies = $movies_query->found_posts;
			$total_pages = $total_movies > 0 ? ceil($total_movies / $posts_per_page) : 0;

			return array(
					'success' => 1,
					'movies' => $movies,
					'total_movies' => $total_movies,
					'total_pages' => $total_pages,
					'current_page' => $paged,
			);
	}



  public static function get_movie($data) {
      $movie_id = $data['id'];
      $movie = get_post($movie_id);

      if (!$movie) {
          return new \WP_Error('message', 'Movie not found', array('status' => 404));
      }

      return array(
					'success' => 1,
          'id' => $movie->ID,
          'title' => $movie->post_title,
          'description' => get_post_meta($movie->ID, 'movie_description', true),
          'year' => get_post_meta($movie->ID, 'year_released', true)
      );
  }
}
