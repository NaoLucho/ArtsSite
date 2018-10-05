$(document).ready(function () {
    $('#_modal_login_submit').attr('disabled', true);

    $('#username, #password')
        .change(function () { checkFormValidity(); })
        .select(function () { checkFormValidity(); })
        .keyup(function () { checkFormValidity(); });

        $('#_modal_login_submit').on('click', function (e) {
            e.preventDefault();
            $.ajax({
                type: $('#login-form').attr('method'),
                url: $('#login-form').attr('action'),
                data: $('#login-form').serialize(),
                success: function (data, status, object) {
                    if (data.success == false) {
                        $('#invalidCredentials').fadeIn();
                    } else {
                        location.reload();
                    }
                }
            });
        });

        //try to replace open.min.js
        // $('#login').on('click', function(c) {
        //     var d = a(this),
        //       e = d.attr("href"),
        //       f = a(d.attr("data-target") || e && e.replace(/.*(?=#[^\s]+$)/, "")),
        //       g = f.data("bs.modal") ? "toggle" : a.extend({
        //         remote: !/#/.test(e) && e
        //       }, f.data(), d.data());
        //     d.is("a") && c.preventDefault(), f.one("show.bs.modal", function(a) {
        //       a.isDefaultPrevented() || f.one("hidden.bs.modal", function() {
        //         d.is(":visible") && d.trigger("focus")
        //       })
        //     }), b.call(f, g, this)
        //   });
});

function checkFormValidity() {
    if ($('#username').val().length != 0 && $('#password').val().length != 0)
        $('#_modal_login_submit').attr('disabled', false);
    else
        $('#_modal_login_submit').attr('disabled', true);
}



// $(document).ready(function () {
//     $('#_submit').attr('disabled', true);

//     $('#username, #password')
//         .change(function () { checkFormValidity(); })
//         .select(function () { checkFormValidity(); })
//         .keyup(function () { checkFormValidity(); });

//     function checkFormValidity() {
//         if ($('#username').val().length != 0 && $('#password').val().length != 0)
//             $('#_submit').attr('disabled', false);
//         else
//             $('#_submit').attr('disabled', true);
//     }

//     $('#_submit').on('click', function (e) {
//         e.preventDefault();
//         //console.log($('form').serialize());
//         $.ajax({
//             type: $('form').attr('method'),
//             url: $('form').attr('action'),
//             data: $('form').serialize(),
            
//             success: function (data, status, object) {
//                 if (data.success == false) {
//                     //console.log(data);
//                     $('#invalidCredentials').fadeIn();
//                 } else {
//                     console.log(data);
//                     // console.log(status);
//                     // console.log(object);
//                     // location.reload();
//                 }
//             }
//         });
//     });
// });

