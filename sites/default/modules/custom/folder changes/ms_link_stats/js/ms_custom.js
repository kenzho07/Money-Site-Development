jQuery(document).ready(function(){
  setTimeout(function(){
    //Get parameter
    var urlParams = new URLSearchParams(location.search);
    var hrefOfLink = urlParams.get('href');
    if (hrefOfLink) {
        //add class to highlight text
        jQuery(".field--name-body iframe").contents().find('a[href="'+hrefOfLink+'"]').css('background-color', 'rgb(255, 255, 0)');
    }
  }, 500);
});