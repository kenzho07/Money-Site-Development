/**
 * @file
 * JavaScript for ms_domain_vocab.
 */

(function ($) {

  Drupal.msDomainVocab = {};

  // Custom function to hide/show Reference fields.
  Drupal.msDomainVocab.hideShowCatField = function(site, sites, allCatPrime, allCats) {
    allCatPrime.find('.field--type-entity-reference').hide();
    allCats.find('.field--type-entity-reference').hide();

    if (site + '_site' in sites) {
      if (sites[site + '_site'].length > 1) {
        var dropdownId = sites[site + '_site'].replace(/\_/g, '-');
        allCatPrime.find('.field--type-entity-reference#edit-field-' + dropdownId + '-primary-wrapper').show();
        allCats.find('.field--type-entity-reference#edit-field-' + dropdownId + '-wrapper').show();
      }
    }
  }

  // Re-enable form elements that are disabled for non-ajax situations.
  Drupal.behaviors.categorySelectSitesBased = {
    attach: function () {
      var sitesList = drupalSettings.msDomainVocab.sitesList;
      var $availSite = $('#edit-field-domain-access');
      var $allCatPrime = $('fieldset#edit-group-sites-categories-url-alias .fieldset-wrapper');
      var $allCats = $('fieldset#group-sites-categories .fieldset-wrapper');

      $allCatPrime.find('.field--type-entity-reference').hide();
      $allCats.find('.field--type-entity-reference').hide();

      // Get current selected sites from edit form.
      var siteSelected = $availSite.find(':selected').val();
      Drupal.msDomainVocab.hideShowCatField(siteSelected, sitesList, $allCatPrime, $allCats);

      $availSite.change(function(i) {
        $availSite.find('option:selected').each(function() {
          var site = $(this).val();
          Drupal.msDomainVocab.hideShowCatField(site, sitesList, $allCatPrime, $allCats);
        });
      });
    }
  };

})(jQuery);
