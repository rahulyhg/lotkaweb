window.onload=function(){
$(".pinns").each(function () {
  var board = $(this);
  if (board.data("spotify-uri"))
    board.children(".area").after(
            '<iframe src="https://embed.spotify.com/?uri=' + 
        board.data("spotify-uri")  + 
      '" frameborder="0" allowtransparency="true"></iframe>'
        );
      
  $.getJSON(
        "https://widgets.pinterest.com/v3/pidgets/boards/" + 
        board.data("board") + 
        "/pins/?callback=?", {sub:'www'}
    ).done(function (re) { 
            if(re.status == "success")
                (re.data.pins||[]).forEach(function (pin) {
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
$(".pinns").on("click", ".pinned", function (event) {
    event.preventDefault();
    $("main").css({
        "background-image": $(this).css("background-image").replace('237x','originals') + ", url(spinner.svg)"
    }).show();
});

$("main").on("click", function () {
    $(this).hide();
}).fadeOut(1500);

$("#shirt_type").on("change", function () {
    var selected_type = $("#shirt_type option:selected").attr('class');
    var selected_size = $("#shirt_size option:selected").val();
    $("#shirt_size").val([]);
    $("#shirt_size option").hide();
    $("#shirt_size option." + selected_type).show();
    $("#shirt_size option." + selected_type + "." + selected_size).prop("selected", true);
}).trigger("change");

$("#info").on('click', function () {
    $(this).fadeOut(200);
});

$("form.ticket aside img, form.ticket aside h2").on('click', function () {
    $("form.ticket fieldset").fadeOut(50);
    $(this).parent().children("fieldset").fadeToggle(200);
});

function tokenHandler(ticket_type, token) {
    $("#info").empty().addClass("loading").fadeIn(100);
    var settings = {
        token: token.id, 
        email: token.email, 
        surname: 'NA',
        type: 'NA',
        size: $("#shirt_size_standard").val(),
        pref: $("#team").val(),
        ticket_type: ticket_type
    }

    if(ticket_type == 'SUPPORT') {
        size = $("#shirt_size").val(),
        settings.type = $("#shirt_type").val();
        settings.surname = $("#surname").val();
    }

    $.post('/charge', settings).done(function (resp) {
        console.log('ok', resp);

        var info_html = resp.error ? "<h1>SORRY!</h1><p>" + resp.error.message + "<em>Sorry for the inconvenience.</em></p>" : "<h1>THANK YOU!</h1><p>Your ticket is reserved and you should get an email soon. <em>Your support means a lot to us!</em></p>";

        $("#info")
            .removeClass("loading")
            .addClass(resp.error ? "fail" : "success")
            .html(info_html).fadeIn(100);

        if (settings.surname != 'NA') {
            $.getJSON('/api/v1/names', function (names) {
                $("#surname").empty();
                names.forEach(function (name) {
                    $("#surname").append($("<option/>").text(name));
                });
                $('form')[0].reset();
            });             
        }
    }).fail(function (re) {
        console.error('fail', re);

        $("#info")
            .removeClass("loading")
            .addClass("fail")
            .html("<h1>SORRY!</h1><p>Something went wrong securing your ticket. <em>Please try again in a little while or contact us.</em></p>").fadeIn(100);
    });
  }

var support = StripeCheckout.configure({
  key: window.stripe.public_key,
  image: 'img/LV-logo-ticket-icon-support.png',
  locale: 'en',
  token: function (token) {
    tokenHandler('SUPPORT', token);
  }
});

var standard = StripeCheckout.configure({
  key: window.stripe.public_key,
  image: 'img/LV-logo-ticket-icon-standard.png',
  locale: 'en',
  token: function (token) {
    tokenHandler('STANDARD', token);
  }
});

$('#supporter_ticket').on('click', function(e) {
  // Open Checkout with further options:
  support.open({
    name: 'LOTKA-VOLTERRA',
    description: 'Supporter Ticket',
    zipCode: true,
    currency: 'sek',
    amount: 360000,
    "closed": function () {
        
    }
  });
  e.preventDefault();
});

$('#standard_ticket').on('click', function(e) {
  // Open Checkout with further options:
  standard.open({
    name: 'LOTKA-VOLTERRA',
    description: 'Standard Ticket',
    zipCode: true,
    currency: 'sek',
    amount: 260000,
    "closed": function () {
        
    }
  });
  e.preventDefault();
}); 

// Close Checkout on page navigation:
window.addEventListener('popstate', function() {
  handler.close();
}); 

$("#contact figure img").on('mousemove', function () { 
  $(this).data('glitch').ones(4); 
}).each(function () {
  var static = $(this).get(0)
  $(this).data('glitch',  
    new Glitch(static, function (img) { static.src = img.src; }));
}).on('mouseleave', function () {
  $(this).data('glitch').reset();
});
  
}