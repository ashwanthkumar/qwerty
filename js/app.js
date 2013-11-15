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
    switch(step){
      case 1:
        $('.navigation .path2').animate({'width': '0'}, 1000);
          $('.circle.mid, .circle.right').animate({'backgroundColor': '#000'});
        $('.navigation .'+object+'-icon').animate({'left': '0'}, 1000);
        break;
      case 2:
        $('.navigation .path2').animate({'width': '50%'}, 1000);
        $('.circle.right').animate({'backgroundColor': '#000'});
        $('.navigation .'+object+'-icon').animate({'left': '50%'}, 1000, function(){
          $('.circle.mid').animate({'backgroundColor': '#1ada22'});
        });
        break;
      case 3:
        $('.navigation .path2').animate({'width': '100%'}, 1000);
        $('.navigation .'+object+'-icon').animate({'left': '100%'}, 1000, function(){
          $('.circle.mid, .circle.right').animate({'backgroundColor': '#1ada22'});
        });
        break;
    }
  };
  qwerty.user_id = getUrlAsParams().user_id;

  //STEP 1
  $('.page.mode div').click(function(){
    qwerty.mode = $(this).attr('class');
    qwerty.animate(2, qwerty.mode);
    $('.page.mode').fadeOut(200, function(){
      $('.page.info').fadeIn(200);
    });
  });

  //STEP 2
  $('.page.info .arrow-back').click(function(){//back
    qwerty.animate(1, qwerty.mode);
    delete qwerty.mode;
    $('.page.info').fadeOut(200, function(){
      $('.page.mode').fadeIn(200);
    });
  });
  var checkFields = function checkFields(){
    if(qwerty.from && qwerty.to && qwerty.date){
      try{ qwerty.xhr && qwerty.xhr.abort();} catch(e){}
      qwerty.xhr = $.ajax({
        url: './bus/search',
        type: 'GET',
        data: {from: qwerty.from, to: qwerty.to, date: qwerty.date},
        success: function(data){
          var html = '';
          data = _.map(_.reduce(data, function(mem,x){mem[x.departure_time]=x; return mem;}, {}), function(V){return V;});//removing duplicate departure_time
          data.sort(function(a,b){if(parseInt(a.departure_time)>parseInt(b.departure_time))return 1;return -1;});
          qwerty.data = data;
          for (var i = data.length - 1; i >= 0; i--) {
            html += '<option value="'+data[i].bus_id+'">'+data[i].departure_time+'</option>';
          };
          $('.page.info .form select').attr('disabled', false).html(html).change(function(){
            qwerty.bus_id = $(this).val();
            try{ qwerty.xhr2 && qwerty.xhr2.abort();} catch(e){}
            $('.page.book .image-container').animate({'opacity': '0.2'}).prepend('<div class="loading">Loading</div>');
            qwerty.xhr2 = $.ajax({//load data for next page
              url: './bus/travel',
              type: 'POST',
              data: {user_id: qwerty.user_id, from: qwerty.from, to: qwerty.to, date: qwerty.date+' '+$(this).find('option[value="'+$(this).val()+'"]').text()+':00', bus_id: qwerty.bus_id},
              success: function(data){
                var travel_id = data.travel_id;
                data = data.data;
                $('.page.book .image-container').animate({'opacity': '1'}).find('.loading').remove();
                //populate seats
                for (var k = data.length - 1; k >= 0; k--) {
                  var seat = data[k],
                      i = Math.floor(+seat.seat_number / seatingArray[0].length),
                      j = +seat.seat_number % seatingArray[0].length;
                  if(seat.matches) {
                    $('.page.book .image-container .number-'+i+j)
                    .addClass('match')
                    .html('\
                        <div class="music" style="width:'+seat.matches.music+'%"></div>\
                        <div class="books" style="width:'+seat.matches.books+'%"></div>\
                        <div class="movies" style="width:'+seat.matches.movies+'%"></div>\
                        <div class="skills" style="width:'+seat.matches.skills+'%"></div>\
                      ').popover({
                      html: true,
                      placement: 'auto top',
                      // trigger: 'hover',
                      title: 'Matching Interests',
                      content: '<span class="music-pre"></span>Music: '+seat.matches.music+'%<br><span class="books-pre"></span>Books: '+seat.matches.books+'%<br><span class="movies-pre"></span>Movies: '+seat.matches.movies+'%<br><span class="skills-pre"></span>Skills: '+seat.matches.skills+'%',
                      container: 'body'
                    })
                  } else {
                    $('.page.book .image-container .number-'+i+j).addClass('booked');
                  }
                };
                $('.seat.available-1:not(.booked):not(.match)').click(function(){
                  $('.seat.available-1:not(.booked):not(.match)').removeClass('selected');
                  $(this).addClass('selected');
                  var ij = $(this).attr('class').match(/number-\d\d/g)[0],
                      i = +ij.substr(-2,1),
                      j = +ij.substr(-1,1);
                  qwerty.seat_number = (i*8)+j;
                  $('.page.book .arrow-next').removeClass('disabled').click(function(){
                    $.ajax({
                      url: './bus/travel/book/'+travel_id,
                      data: {user_id: qwerty.user_id, seat_number: qwerty.seat_number},
                      success: function(){
                        window.location.href="./thank_you.html";
                      },
                      error: function(){
                        //alert('server error');
                        throw 'server error';
                      }
                    });
                  });
                });
              },
              error: function(){
                //alert('server error');
                throw 'server error';
              },
              complete: function(){}
            });
            for (var i = qwerty.data.length - 1; i >= 0; i--) {//show in page
              var bus = qwerty.data[i];
              if(qwerty.bus_id == bus.bus_id){
                $('.page.info .form .travel-info')
                  .show().find('.vehicle')
                  .html(bus.travels +'('+bus.bus_type+')')
                  .parent().find('.fare').html(bus.fare);
                break;
              }
            };
          });
          $('.page.info .arrow-next').removeClass('disabled').click(function(){
            qwerty.animate(3, qwerty.mode);
            $('.page.info').fadeOut(200, function(){
              $('.page.book').fadeIn(200);
            });
          });
        },
        error: function(){
          //alert('server error');
          throw 'server error';
        },
        complete: function(){}
      });
    }
  };
  $('.page.info .form .date').datepicker().on('change',function(ev){//date change
    qwerty.date = $.datepicker.formatDate('yymmdd', $('.date').datepicker("getDate"));
    checkFields();
  });
  $('.page.info .form').find('.from,.to').keyup(function(){//from,to change
    qwerty[$(this).attr('class')] = $(this).val();
    checkFields();
  });
});


//matches: music, books, movies, skills
