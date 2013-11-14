function getUrlAsParams(){
  return _.reduce(window.location.search.substr(1).split('&'), function(mem, param){ var split = param.split('='); mem[split[0]] = split[1]; return mem;}, {});
}
qwerty = {};
$(function(){
  //Initialization
  var html = "",
      seatingArray = [
    [1,1,1,1,1,1,1,1],
    [1,1,1,1,1,1,1,1],
    [0,0,0,0,0,0,0,1],
    [0,1,1,1,1,1,1,1],
    [1,1,1,1,1,1,1,1]
  ];
  for(var i=0; i<seatingArray.length; i++){
    html += "<div class='row'>";
    for(var j=0; j<seatingArray[i].length; j++){
      html += "<div class='seat available-"+seatingArray[i][j]+" number-"+i+j+"'></div>"
    }
    html += "</div>";
  }
  $('.page.book .image-container').html(html);
  qwerty.animate = function animate(step, object){
    //
  };

  //STEP 1
  $('.page.mode div').click(function(){
    qwerty.mode = $(this).attr('class');
    qwerty.animate(2, $(this).attr('class'));
    $('.page.mode').fadeOut(200, function(){
      $('.page.info').fadeIn(200);
    });
  });

  //STEP 2
  $('.page.info .arrow-back').click(function(){
    qwerty.animate(1, qwerty.mode);
    delete qwerty.mode;
    $('.page.info').fadeOut(200, function(){
      $('.page.mode').fadeIn(200);
    });
  });

  $('.page.info .form .date').datepicker().on('change',function(ev){
    $.datepicker.formatDate('yyyymmdd', $('.date').datepicker("getDate"));
  });
});
