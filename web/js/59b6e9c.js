jQuery(document).ready(function () {
    $('#new_structure_membership_btn').on('click', function(e) {
        e.preventDefault();
        $('#structure_choice').show();
        $('.validate_new_structure_btn').hide();
    });

    $('#cancel_new_structure_btn').on('click', function(e) {
        $('#structure_choice').hide();
    });

    $('.validate_new_structure_btn').on('click', function (e) {
    });

    $('.structure_list_profile').select2();

    $('.structure_list_profile').on('select2:selecting', function(e) {
        $('.validate_new_structure_btn').show();
    });
    
    $('.structure_list_profile').on('change', function() {
        var text =  $('.structure_list_profile').select2('data')[0].text;
        text = 'Valider votre inscription pour la structure ' + text;
        $(".validate_new_structure_btn").text(text);
    });

});
