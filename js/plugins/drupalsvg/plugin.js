(function ($, Drupal, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('drupalsvg',
  {

    init: function (editor) {

      editor.addCommand('drupalsvg', new CKEDITOR.dialogCommand('drupalsvg', {
        allowedContent: 'img[!src,!class]',
/*        requiredContent: 'a[href,data-entity-type,data-entity-uuid]',*/
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor, data) {
          var dialogSettings = {
            title: 'Insert svg',
            dialogClass: 'editor-svg-dialog'
          };
          var dialogSaveCallback = function (data) {
            var element = new CKEDITOR.dom.element( 'img' );
            element.setAttributes(data.attributes);
              var $width = 'width:' + " " + data.width + "%;";
            element.setAttribute('style', $width);
            element.setAttribute('alt', data.alt);
            element.setAttribute('class', data.align);
            editor.insertElement(element);
          };
          Drupal.ckeditor.openDialog(editor, Drupal.url('ckeditor_svg/dialog/svg/' + editor.config.drupal.format), {}, dialogSaveCallback, dialogSettings);
        }
      }));

      editor.ui.addButton('DrupalSvg', {
        label: 'Svg',
        toolbar: '',
        command: 'drupalsvg',
        icon: this.path + 'icons/svg.png'
      });

    }
  });

})(jQuery, Drupal, CKEDITOR);
