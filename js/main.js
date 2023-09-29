var idsToTerms = {};
var idsToTermsInput = document.getElementById('one_wp_feed_rss_monitor_ids_to_terms');
if (idsToTermsInput != null && idsToTermsInput.value != null && idsToTermsInput.value != '') {
  idsToTerms = JSON.parse(idsToTermsInput.value);
  for (const [id, term] of Object.entries(idsToTerms)) {
    let inputElement = document.querySelector('input[data-id="' + id + '"]');
    if (inputElement)
      inputElement.value = term;
  }
}

var termInputs = document.querySelectorAll('#one_wp_feed_rss_monitor_form .term');
if (termInputs != null && termInputs.length > 0) {
  termInputs.forEach((termInput) => {
    termInput.addEventListener('keyup', function() {
      if (this.value != null && this.value != '')
        idsToTerms[this.getAttribute('data-id')] = this.value;
      else
        delete idsToTerms[this.getAttribute('data-id')];
    });
  });
}

var form = document.getElementById('one_wp_feed_rss_monitor_form');
if (form) {
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    let feed_url_list = [];
    document.getElementsByName('one_wp_feed_rss_monitor_feed_url').forEach(input => {
      feed_url_list.push(input.value);
    });
    let default_category_id = document.querySelector('input[name="one_wp_feed_rss_monitor_default_cat"]:checked').value;

    // settings save fetch
    let formData = new FormData();
    formData.append('action', 'one_wp_feed_rss_monitor_save');
    formData.append('feed_url_list', JSON.stringify(feed_url_list));
    formData.append('default_category_id', default_category_id);
    formData.append('ids_to_terms', JSON.stringify(idsToTerms));

    fetch(ajaxurl, {
      method: 'POST',
      body: formData
    })
    .then(function(response) {
      return response.text();
    })
    .then((text) => {
      if (text != null && text != '')
        alert(text);
    });
  });
}

var jobBtn = document.getElementById('one_wp_feed_rss_monitor_job_btn');
if (jobBtn) {
  jobBtn.addEventListener('click', function() {
    document.getElementById('one_wp_feed_rss_monitor_job_response').innerHTML = 'Loading...';
    // job run fetch
    let formData = new FormData();
    formData.append('action', 'one_wp_feed_rss_monitor_update_posts_episodes');

    fetch(ajaxurl, {
      method: 'POST',
      body: formData
    })
    .then(function(response) {
      return response.text();
    })
    .then((text) => {
      if (text != null && text != '')
        document.getElementById('one_wp_feed_rss_monitor_job_response').innerHTML = text;
    });
  });
}

var addFeedBtn = document.getElementById('one_wp_feed_rss_monitor_add_btn');
if (addFeedBtn) {
  addFeedBtn.addEventListener('click', function(e) {
    e.preventDefault();

    let input = document.createElement('input');
    input.setAttribute('type', 'text');
    input.setAttribute('name', 'one_wp_feed_rss_monitor_feed_url');
    input.classList.add('regular-text');

    let container = document.getElementById('one_wp_feed_rss_monitor_feeds_container');
    container.appendChild(input);
  });
}