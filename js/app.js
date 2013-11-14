function getUrlAsParams(){
  return _.reduce(window.location.search.substr(1).split('&'), function(mem, param){ var split = param.split('='); mem[split[0]] = split[1]; return mem;}, {});
}
$(function(){
  $('.page').fadeOut();
  $('.page.book').fadeIn();

  $('.page.info .form .date').datepicker().on('change',function(ev){
    $('.date').datepicker( "getDate" );//Wed Nov 06 2013 00:00:00 GMT+0530 (India Standard Time)
  });
});
