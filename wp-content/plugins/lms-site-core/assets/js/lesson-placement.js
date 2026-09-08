document.addEventListener('click', async function (event) {
 const button = event.target.closest('.lms-save-placement');
 if (!button) return;
 const row = button.closest('.lms-placement-row');
 const box = row.closest('.lms-lesson-placements');
 const select = row.querySelector('select');
 const status = row.querySelector('[role="status"]');
 const section = select.value;
 if (section === '0' && row.dataset.current !== '0' && !window.confirm('Gỡ bài khỏi khóa này? Nội dung bài và các khóa khác vẫn giữ nguyên.')) return;
 button.disabled = true; select.disabled = true; status.textContent = 'Đang lưu…';
 try {
  const body = new URLSearchParams({action:'lms_lesson_placement',nonce:box.dataset.nonce,lesson_id:box.dataset.lesson,course_id:row.dataset.course,section_id:section,expected_section:row.dataset.current});
  const response = await fetch(window.ajaxurl, {method:'POST',credentials:'same-origin',body});
  const result = await response.json();
  if (!result.success) throw new Error(result.data?.message || 'Không lưu được. Reload để kiểm tra trạng thái trước khi thử lại.');
  row.dataset.current = String(result.data.section_id);
  status.textContent = result.data.message;
 } catch (error) { status.textContent = error.message; }
 finally { button.disabled = false; select.disabled = false; }
}, true);
