window.onload = function() {
  /**
  | PIN BOARDS
  **/
  
  $("main.pinns").on("click", function() {
    $(this).hide();
  }).fadeOut(1500);

  $(".pinns").each(function() {
    var board = $(this);
    if (board.data("spotify-uri"))
      board.children(".area").after(
        '<iframe src="https://embed.spotify.com/?uri=' +
        board.data("spotify-uri") +
        '" frameborder="0" allowtransparency="true"></iframe>'
      );

    $.getJSON(
      "https://widgets.pinterest.com/v3/pidgets/boards/" +
      board.data("board") +
      "/pins/?callback=?", {
        sub: 'www'
      }
    ).done(function(re) {
      if (re.status == "success")
        (re.data.pins || []).forEach(function(pin) {
          board.append(
            $("<div/>").css({
              "background-color": pin['dominant_color'],
              "background-image": "url(" + pin.images['237x'].url + ")",
              "cursor": 'pointer'
            }).addClass('pinned')
          );
        });
    });
  });
  
  $(".pinns").on("click", ".pinned", function(event) {
    event.preventDefault();
    $("main.pinns").css({
      "background-image": $(this).css("background-image").replace('237x', 'originals') + ", url(spinner.svg)"
    }).show();
  });
  
  /**
  | STRIPE TICKETS
  **/
  
  $("#shirt_type").on("change", function() {
    var selected_type = $("#shirt_type option:selected").attr('class');
    var selected_size = $("#shirt_size option:selected").val();
    
    console.log(selected_type, selected_size)
    
    $("#shirt_size").val([]);
    $("#shirt_size option").hide();
    $("#shirt_size option." + selected_type).show();
    $("#shirt_size option." + selected_type + "." + selected_size).prop("selected", true);
  }).trigger("change");

  $("#info").on('click', function() {
    $(this).fadeOut(200);
  });
  
  function tokenHandler(ticket_type, token) {
    $("#info").empty().addClass("loading").fadeIn(100);

    var settings = {
      token: token.id,
      email: token.email,
      surname: 'NA',
      type: 'NA',
      size: 'NA',
      pref: 'NA',
      ticket_type: ticket_type.sku,
      csrf_name: $("[name='csrf_name']").val(),
      csrf_value: $("[name='csrf_value']").val()
    }
    
    if (ticket_type.show.surname) settings.surname = $(".surname."+ticket_type.sku).val();
    if (ticket_type.show.shirtType) settings.type = $(".shirt_type."+ticket_type.sku).val();
    if (ticket_type.show.size) settings.size = $(".size."+ticket_type.sku).val();
    if (ticket_type.show.teamPreference) settings.pref = $(".team."+ticket_type.sku).val();

    $.post($('form.ticket').attr('action'), settings).done(function(resp) {
      console.log('ok', resp);

      var info_html = resp.error ? "<h1>SORRY!</h1><p>" + resp.error.message + "<em>Sorry for the inconvenience.</em></p>" : 
      "<h1>THANK YOU!</h1><p>Your ticket is reserved and you should get an email soon. <em>Your support means a lot to us!</em></p>";

      $("#info")
        .removeClass("loading")
        .addClass(resp.error ? "fail" : "success")
        .html(info_html).fadeIn(100);

      if (settings.surname != 'NA') {
        $.getJSON('./api/v1/names', function(resp) {
          var names = resp.data.surnames;
          $("#surname").empty();
          names.forEach(function(name) {
            $("#surname").append($("<option/>").text(name.surname));
          });
          $('form')[0].reset();
        });
      }
    }).fail(function(re) {
      console.error('fail', re);

      $("#info")
        .removeClass("loading")
        .addClass("fail")
        .html("<h1>SORRY!</h1><p>Something went wrong securing your ticket. <em>Please try again in a little while or contact us.</em></p>").fadeIn(100);
    });
  }

  window.stripe.ticket_types.forEach(function (ticket) {
    var config = StripeCheckout.configure({
      key: window.stripe.public_key,
      image: ticket.img,
      locale: 'en',
      token: function(token) {
        tokenHandler(ticket, token);
      }
    });
    
    $('.buy_ticket.' + ticket.sku).on('click', function(e) {
      // Open Checkout with further options:
      config.open({
        name: 'LOTKA-VOLTERRA',
        description: ticket.description,
        zipCode: true,
        currency: 'sek',
        amount: ticket.price,
        "closed": function() {

        }
      });
      e.preventDefault();
    }).data('handler', config).prop('disabled', false);
  });

  if(document.location.pathname.indexOf('ticket/') != -1 && !$("#ticketsform").is(':visible')) {
    $("#ticketsform").show();
  }
  
  // Close Checkout on page navigation:
  window.addEventListener('popstate', function() {
    $('.buy_ticket').each(function () {
      if($(this).data('handler')) $(this).data('handler').close();
    });
  });

  $("#paybystripe").click(function (e) {
    $("#ticketsform").show();
  });
  
  /**
  | Orgs glitch portraits
  **/
  window.glitchSetup = function () {
    var static = $(this).get(0)
    $(this).data('glitch',
      new Glitch(static, function(img) {
        static.src = img.src;
      }));
  }
  
  $("#organizers figure img, img.portrait:not(.lazy)").on('mousemove', function() {
    $(this).data('glitch').ones(4);
  }).each(glitchSetup).on('mouseleave', function() {
    $(this).data('glitch').reset();
  });
  
  $('.lazy').Lazy({
    afterLoad: function(element) {
      element.attr('src', element.data('src'));
      element.on('mousemove', function() {
        $(this).data('glitch').ones(4);
      }).each(glitchSetup).on('mouseleave', function() {
        $(this).data('glitch').reset();
      });
    }, 
    onFinishedAll: function() {
        $('#works-grid').isotope();
    }    
  });
  
  $('.relationship_characters').on('click', '.relationship_remove_from', function () {
    var char_id = $(this).data('character-id');
    $(this).parent().parent().fadeOut(200, function () { 
      var remove_char =  $('[name="character_ids[]"] option[value=' + char_id + ']');
      remove_char.prop('selected', false);
      remove_char.detach().appendTo('[name=all_characters]');
      $(this).remove();
    });
  });
  
  var container_template = $('<div class="col-sm-6 col-md-6 col-lg-6 relationship_character"><div class="content-box"><div class="content-box-image" style="opacity:.6;"><a href="" alt=""><img src="" alt=""></a></div><a href=""><h3 class="content-box-title font-alt"></h3></a><div class="relationship_remove_from"><i class="fa fa-times" title="Remove Character From Relationship"></i></div></div></div>');
  
  $('.add_character').on('click', function (e) {
    e.preventDefault();
    var sel = $('[name=all_characters]');
    var opt = sel.find('option:selected');
    var char = opt.data();
    
    if(char.name && sel.val()) {
      var container = container_template.clone();

      container.find('a').attr('href', char.link);
      container.find('h3').text(char.name);
      container.find('.relationship_remove_from').data('character-id', char.id);
      container.find('img').attr({
        src: char.portrait, alt: char.name 
      });
      container.hide();
      $('.relationship_characters').append(container);
      $('[name=all_characters] option:selected').detach().appendTo('[name="character_ids[]"]').prop('selected', true);
      $('[name=all_characters]').val(null).change();
     
      container.fadeIn(150);
    }    
  });
  
  $(".list_items").on("click", ".add_list_item", function (e) {
    e.preventDefault();
    $addDiv = $(this).parent().parent().clone();
    $addDiv.find('input').each(function () { $(this).val(null); })
    $(this).parent().parent().after($addDiv);
    $(this).parent().parent().find('button')
      .removeClass('btn-green add_list_item').addClass('btn-warning remove_list_item').text('-');
  });

  $(".list_items").on("click", ".remove_list_item", function (e) {
    e.preventDefault();
    $addDiv = $(this).parent().parent().remove();
  });
  
  $(".form.relation").on('submit', function (e) {
    if(!$('[name="character_ids[]"]').val().length) {
      if (!window.confirm("Are you sure? This will delete the realtionship")) e.preventDefault();
    }
  })
};