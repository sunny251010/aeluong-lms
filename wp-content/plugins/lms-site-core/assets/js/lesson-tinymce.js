/* Native TinyMCE controls with a focused YouTube URL dialog. */
(function () {
 tinymce.PluginManager.add('lms_lesson_tools', function (editor) {
  editor.addButton('lms_youtube', {
   text: 'YouTube', tooltip: 'Thêm video YouTube bằng đường link', icon: false,
   onclick: function () {
    editor.windowManager.open({title:'Thêm video YouTube',body:[{type:'textbox',name:'url',label:'Link YouTube',size:60}],onsubmit:function(event){
     var id = '';
     try {
      var url = new URL(event.data.url.trim());
      if (!['https:','http:'].includes(url.protocol)) throw new Error();
      if (url.hostname === 'youtu.be') id = url.pathname.slice(1);
      else if (['youtube.com','www.youtube.com','m.youtube.com','www.youtube-nocookie.com'].includes(url.hostname)) {
       id = url.pathname === '/watch' ? url.searchParams.get('v') : (/^\/(?:embed|shorts|live)\/([A-Za-z0-9_-]{11})\/?$/.exec(url.pathname)||[])[1];
      }
      if (!/^[A-Za-z0-9_-]{11}$/.test(id || '')) throw new Error();
     } catch (_) { editor.windowManager.alert('Hãy nhập link video YouTube hợp lệ.'); return false; }
     editor.insertContent('<div class="lms-lesson-video"><iframe title="Video bài học YouTube" src="https://www.youtube-nocookie.com/embed/'+id+'" width="960" height="540" allowfullscreen="allowfullscreen" loading="lazy"></iframe></div><p></p>');
    }});
   }
  });
 });
})();
