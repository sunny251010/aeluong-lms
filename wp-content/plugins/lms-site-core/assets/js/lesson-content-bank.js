/* Extend the course-only template. Native LearnPress handles tabs, search and adding. */
(function () {
 function extend() {
  const template = document.getElementById('lp-tmpl-select-course-items-bank');
  if (!template || template.dataset.lmsSharedReady) return;
  if (!template.innerHTML.includes('<ul class="tabs">')) return;
  template.innerHTML = template.innerHTML.replace('</ul>', '<li data-type="lms_shared_lesson" class="tab"><a href="#">Bài học dùng chung</a></li></ul>');
  template.dataset.lmsSharedReady = '1';
 }
 extend();
 new MutationObserver(extend).observe(document.body,{childList:true,subtree:true});
})();
