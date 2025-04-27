<div class="wrap">
  <h2>Settings</h2>
  <form method="post" action="" id="one_wp_feed_rss_monitor_form">
    <table class="form-table">
      <tr>
        <th scope="row"><label for="one_wp_feed_rss_monitor_feed_url">Feed RSS URLs:</label></th>
        <td id="one_wp_feed_rss_monitor_feeds_container">
          <?php if (!empty(esc_attr($options['feed_url_list']))) : ?>
            <?php
              $feedUrlList = json_decode($options['feed_url_list']);
              foreach ($feedUrlList as $feedUrl) :
            ?>
              <input 
                type="text"
                name="one_wp_feed_rss_monitor_feed_url" 
                value="<?= $feedUrl ?>" 
                class="regular-text" 
              />
            <?php endforeach; ?>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td></td>
        <td colspan="2">
          <button class="button-secondary" id="one_wp_feed_rss_monitor_add_btn">Add feed</button>
        </td>
      </tr>
      <?php
        if (!empty($categories)) :
          $defaultCategoryId = esc_attr($options['default_category_id']);
      ?>
          <tr>
            <td colspan="3"><strong>Default category</strong> (all episodes will be assigned to this category during auto-publish)</td>
          </tr>
          <tr>
            <td>Select default category:</td>
            <td colspan="2">
              <select name="one_wp_feed_rss_monitor_default_cat" id="one_wp_feed_rss_monitor_default_cat">
                <option value="">Categories</option>
                <?php foreach ($categories as $cat) : ?>
                  <option 
                    value="<?= $cat->term_id ?>" 
                    <?= (($defaultCategoryId == $cat->term_id) ? 'selected' : '') ?>
                  >
                    <?= $cat->name ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <tr>
            <td colspan="3">Assign specific terms <strong>(found in episodes titles)</strong> to post categories during auto-publish.</td>
          </tr>
          <tr>
            <th>Category</th>
            <th colspan="2">Term</th>
          </tr>
      <?php
          foreach ($categories as $cat) :
      ?>
            <tr>
              <td><?= $cat->name ?></td>
              <td colspan="2">
                <input 
                  placeholder="Leave blank to avoid this category" 
                  class="term regular-text" 
                  type="text" 
                  name="one_wp_feed_rss_monitor_<?= $cat->slug ?>_term" 
                  id="one_wp_feed_rss_monitor_<?= $cat->slug ?>_term"
                  data-id="<?= $cat->term_id ?>"
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
