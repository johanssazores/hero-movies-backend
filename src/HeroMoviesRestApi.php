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
					return new \WP_Error('authentication_failed', __('Invalid username or password.', 'text-domain'), array('status' => 401));
			}

			$token = wp_generate_password(64, false);

			$expiration_time = time() + (1 * HOUR_IN_SECONDS); 
			update_user_meta($user->ID, 'auth_token', $token);
			update_user_meta($user->ID, 'auth_token_expiration', $expiration_time);

			return array(
					'token' => $token,
					'expires_in' => 3600,
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
							return new \WP_Error('token_expired', __('Token has expired.', 'text-domain'), array('status' => 401));
					}
			}

			return new \WP_Error('token_invalid', __('Invalid token.', 'text-domain'), array('status' => 401));
	}

  public static function get_movies() {
      $args = array(
          'post_type' => 'movies',
          'posts_per_page' => -1
      );

      $movies_query = new \WP_Query($args);
      $movies = array();

      if ($movies_query->have_posts()) {
          while ($movies_query->have_posts()) {
              $movies_query->the_post();
              $movies[] = array(
                  'id' => get_the_ID(),
                  'title' => get_the_title(),
                  'description' => get_post_meta(get_the_ID(), 'movie_description', true),
                  'year' => get_post_meta(get_the_ID(), 'year_released', true)
              );
          }
      } 
      wp_reset_postdata();

      return $movies;
  }

  public static function get_movie($data) {
      $movie_id = $data['id'];
      $movie = get_post($movie_id);

      if (!$movie) {
          return new \WP_Error('error', 'Movie not found', array('status' => 404));
      }

      return array(
          'id' => $movie->ID,
          'title' => $movie->post_title,
          'description' => get_post_meta($movie->ID, 'movie_description', true),
          'year' => get_post_meta($movie->ID, 'year_released', true)
      );
  }
}
