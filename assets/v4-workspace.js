document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('.uld-v4-toggle').forEach(function(btn){
    btn.addEventListener('click',function(){
      var panel=document.getElementById(btn.dataset.target);
      if(!panel)return;
      panel.hidden=!panel.hidden;
      btn.textContent=panel.hidden?'Assign':'Close';
    });
  });

  document.querySelectorAll('.uld-v4-assign-panel form').forEach(function(form){
    var kindInputs=form.querySelectorAll('input[name="assign_kind"]');
    function refresh(){
      var selected=form.querySelector('input[name="assign_kind"]:checked');
      var kind=selected?selected.value:'student';
      form.querySelectorAll('.uld-v4-choice').forEach(function(card){
        var isCourse=card.classList.contains('uld-v4-course-choice');
        card.style.display=(kind==='course' ? (isCourse?'flex':'none') : (isCourse?'none':'flex'));
        var input=card.querySelector('input[name="target_id"]');
        if(input && card.style.display==='none') input.checked=false;
      });
    }
    kindInputs.forEach(function(input){input.addEventListener('change',refresh);});
    refresh();
  });
});
