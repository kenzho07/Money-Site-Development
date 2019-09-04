(function ($, Drupal) {

  'use strict';

  $(document).ready(function(){
  
      setTimeout(function(){ 
        var valInbox = 0; 
        if (jQuery('.private-message-page-link').length > 0) {
          //display color
          let textColorClass = ""; //default          
          valInbox = jQuery('.private-message-page-link').html();
          if(valInbox>0){
            textColorClass = "textColorClass";
          }
          jQuery("#toolbar-link-page_manager-page_view_private_message_private_message-panels_variant-0")
            .prepend('<span id="private_message-menu-notification" class="'+textColorClass+'">('+valInbox+')</span> ')
        }
        jQuery('.block-private-message-notification-block').css('display','none');
      });

  })

})(jQuery, Drupal);

