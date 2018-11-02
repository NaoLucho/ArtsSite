$(document).ready(function(){

 // Open menu onclick
 $('.toggleMenu').on('click', function(e) {
   e.preventDefault();
   $('.mainNav').toggleClass('open');
   $(this).addClass('hidden');
 });

 // Close menu
 $('.mainNav .closeMenu, .closeMenu--sm').on('click', function(e){
   e.preventDefault();
   $('.mainNav').removeClass('open');
   $('.toggleMenu').removeClass('hidden');
 });

 darkNav();

 $('#form-multiple-search [class*="form__"] > label').on('click', function(){
   $(this).parent().toggleClass('active');
 });

 // $('[data-toggle="tooltip"]').tooltip();
 $('[data-toggle="popover"]').popover();

 // Select all checkbox
 $('input[data-select-all]').on('change', function(){
   var select = $(this).data('select-all'),
   status = this.checked; // "select all" checked status
    $('input[type="checkbox"][name="'+select+'"]').each(function(){ //iterate all listed checkbox items
        this.checked = status; //change ".checkbox" checked status
    });
 });

 $('#homeCarousel .owl-carousel').owlCarousel({
   items: 1,
   loop: true,
   dots: false,
   margin: 0,
   navContainerClass: 'container owl-nav',
   responsive: {
     0: {
       nav: false
     },
     768: {
       nav: true
     }
   }
 });

 $('.two-item-carousel').owlCarousel({
    loop:true,
    margin:30,
    responsiveClass:true,
    responsive:{
        0:{
            items:1,
            nav:true
        },
        992:{
            items:2,
            nav:true
        }
    }
});

});

$(window).resize(function(){ darkNav(); });
$(window).scroll(function(){ darkNav(); });


// Fonction pour passer la navigation avec un fond noir lors du scroll
function darkNav() {
  var heroHeight = $('.page-hero').height(),
      windowTop = $(window).scrollTop(),
      header = $('.header');

  if(windowTop > (heroHeight - 200)) {
    header.addClass('scrollable');
  } else {
    header.removeClass('scrollable');
  }
}
