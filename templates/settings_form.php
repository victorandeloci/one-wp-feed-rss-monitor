<div class="wrap">
  <h2>Settings</h2>
  <form method="post" action="" id="one_wp_feed_rss_monitor_form">
    <table class="form-table">
      <tr>
        <th scope="row"><label for="one_wp_feed_rss_monitor_feed_url">Feed RSS URL:</label></th>
        <td>
          <input 
            type="text" 
            id="one_wp_feed_rss_monitor_feed_url" 
            name="one_wp_feed_rss_monitor_feed_url" 
            value="<?= esc_attr($options['feed_url']) ?>" 
            class="regular-text" 
          />
        </td>
      </tr>
      <tr>
        <td colspan="3">Assign specific terms <strong>(found in episodes titles)</strong> to post categories during auto-publish</td>
      </tr>
      <?php
        if (!empty($categories)) :
          $defaultCategoryId = esc_attr($options['default_category_id']);
      ?>
          <tr>
            <th>Category</th>
            <th>Term</th>
            <th>Default category?</th>
          </tr>
      <?php
          foreach ($categories as $cat) :
      ?>
            <tr>
              <th><?= $cat->name ?></th>
              <td>
                <input 
                  placeholder="Leave blank to avoid this category" 
                  class="term" 
                  type="text" 
                  name="one_wp_feed_rss_monitor_<?= $cat->slug ?>_term" 
                  id="one_wp_feed_rss_monitor_<?= $cat->slug ?>_term"
                  data-id="<?= $cat->term_id ?>"
                />
              </td>
              <td>
                <input
                  type="radio"
                  value="<?= $cat->term_id ?>"
                  name="one_wp_feed_rss_monitor_default_cat"
                  id="one_wp_feed_rss_monitor_default_cat_<?= $cat->slug ?>"
                  <?= ($defaultCategoryId == $cat->term_id) ? 'checked' : '' ?>
                />
              </td>
            </tr>
      <?php
          endforeach;
        endif;
      ?>
    </table>
    <input 
      type="hidden" 
      name="one_wp_feed_rss_monitor_ids_to_terms"
      id="one_wp_feed_rss_monitor_ids_to_terms"
      value='<?= $options['ids_to_terms'] ?>'
    />
    <p class="submit">
      <input type="submit" name="one_wp_feed_rss_monitor_submit" class="button-primary" value="Save Settings" />
    </p>
  </form>
</div>