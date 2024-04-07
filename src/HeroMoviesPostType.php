<?php
namespace HeroMovies;

class HeroMoviesPostType {
  public static function register() {
    add_action('init', [__CLASS__, 'init']);
    add_action('admin_menu', [__CLASS__, 'add_import_page']); 
  }

  public static function init() {
      register_post_type('movies',
          array(
              'labels' => array(
                  'name' => __('Movies'),
                  'singular_name' => __('Movie')
              ),
              'public' => true,
              'has_archive' => true,
              'supports' => array('title', 'thumbnail'),
              'menu_icon' => 'dashicons-video-alt3', 
              'menu_position' => 2,
          )
      );

      add_action('add_meta_boxes', [__CLASS__, 'add_movie_meta_box']);
      add_action('save_post', [__CLASS__, 'save_movie_meta_data']);
      self::enqueue_hero_assets();
  }

  public static function add_import_page() {
    add_submenu_page(
      'edit.php?post_type=movies',
      __('Import Movies', 'textdomain'),
      __('Import Movies', 'textdomain'),
      'manage_options',
      'import-movies',
      [__CLASS__, 'import_movies_page']
    );
  }

  public static function import_movies_page() {
    if (isset($_FILES['movies_csv']) && $_FILES['movies_csv']['error'] == UPLOAD_ERR_OK) {
      $csv_file = $_FILES['movies_csv']['tmp_name'];
      $movies = array_map('str_getcsv', file($csv_file));

      // Extract header row to identify column indices
      $header = array_shift($movies);
      $title_index = array_search('Title', $header);
      $description_index = array_search('Description', $header);
      $year_released_index = array_search('Year Released', $header);

      foreach ($movies as $movie) {
        $title = isset($movie[$title_index]) ? $movie[$title_index] : '';
        $description = isset($movie[$description_index]) ? $movie[$description_index] : '';
        $year_released = isset($movie[$year_released_index]) ? $movie[$year_released_index] : '';

        $post_id = wp_insert_post(array(
          'post_title'    => $title,
          'post_content'  => '',
          'post_type'     => 'movies',
          'post_status'   => 'publish'
        ));

        if ($post_id) {
          if (!empty($description)) {
            update_post_meta($post_id, 'movie_description', sanitize_textarea_field($description));
          }
          if (!empty($year_released)) {
            update_post_meta($post_id, 'year_released', sanitize_text_field($year_released));
          }
        }
      }

      echo '<div class="updated"><p>Movies imported successfully!</p></div>';
    }

    ?>
    <div class="wrap">
      <h1><?php _e('Import Movies', 'textdomain'); ?></h1>
      <form method="post" enctype="multipart/form-data">
        <input type="file" name="movies_csv" accept=".csv">
        <input type="submit" class="button button-primary" value="<?php _e('Import', 'textdomain'); ?>">
      </form>
    </div>
    <?php
  }


  public static function add_movie_meta_box() {
    add_meta_box(
      'movie_info',
      __('Movie Information'),
      [__CLASS__, 'movie_meta_box_callback'],
      'movies',
      'normal',
      'default'
    );
  }

  public static function movie_meta_box_callback($post) {
    wp_nonce_field('movie_meta_box', 'movie_meta_box_nonce');

    $movie_description = get_post_meta($post->ID, 'movie_description', true);
    $year_released = get_post_meta($post->ID, 'year_released', true);

    echo '<div class="movie-meta-box-container">';
    echo '<label for="movie_description">' . __('Movie Description') . '</label>';
    echo '<textarea id="movie_description" class="movie-meta-box-field" name="movie_description">' . esc_html($movie_description) . '</textarea><br>';

    echo '<label for="year_released">' . __('Year Released') . '</label>';
    echo '<input type="text" id="year_released" class="movie-meta-box-field" name="year_released" value="' . esc_attr($year_released) . '">';
    echo '</div>';
  }

  public static function save_movie_meta_data($post_id) {
    if (!isset($_POST['movie_meta_box_nonce'])) {
      return;
    }

    if (!wp_verify_nonce($_POST['movie_meta_box_nonce'], 'movie_meta_box')) {
      return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    if (!current_user_can('edit_post', $post_id)) {
      return;
    }

    if (isset($_POST['movie_description'])) {
      update_post_meta($post_id, 'movie_description', sanitize_textarea_field($_POST['movie_description']));
    }

    if (isset($_POST['year_released'])) {
      update_post_meta($post_id, 'year_released', sanitize_text_field($_POST['year_released']));
    }
  }

  public static function enqueue_hero_assets() {
      wp_enqueue_script('hero-movie-scripts', plugins_url('assets/js/scripts,js', __FILE__), array(), '1.0', 'all');
      wp_enqueue_style('hero-movie-styles', plugins_url('assets/css/styles.css', __FILE__), array(), '1.0', 'all');
  }
}
