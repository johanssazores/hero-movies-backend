<?php
namespace HeroMovies;

class HeroMoviesBackend {
  public static function add_menu() {
    add_action('admin_menu', [__CLASS__, 'menu']);
  }

  public static function menu() {
      add_menu_page('Movies', 'Movies', 'manage_options', 'custom-movies', [__CLASS__, 'movies_page']);
  }

  public static function movies_page() {
      $args = array(
          'post_type' => 'movies',
          'posts_per_page' => -1
      );

      $movies_query = new \WP_Query($args);

      if ($movies_query->have_posts()) {
          echo '<ul>';
          while ($movies_query->have_posts()) {
              $movies_query->the_post();
              echo '<li><a href="' . get_edit_post_link() . '">' . get_the_title() . '</a></li>';
          }
          echo '</ul>';
      } else {
          echo 'No movies found.';
      }
      wp_reset_postdata();
  }
}
