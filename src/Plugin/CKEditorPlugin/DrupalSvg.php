<?php

/**
 * @file
 * Contains \Drupal\ckeditor_svg\Plugin\CKEditorPlugin\DrupalSvg.
 */

namespace Drupal\ckeditor_svg\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;


/**
 * Defines the "drupalsvg" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalsvg",
 *   label = @Translation("Svg")
 * )
 *
 */
class DrupalSvg extends CKEditorPluginBase {

  public function getFile() {
    return drupal_get_path('module', 'ckeditor_svg') . '/js/plugins/drupalsvg/plugin.js';
  }

  public function getLibraries(Editor $editor) {
    return array(
      'core/drupal.ajax',
    );
  }

  public function getConfig(Editor $editor) {
    return array();
  }

  public function getButtons() {
    return array(
      'DrupalSvg' => array(
        'label' => t('Svg'),
        'image' => drupal_get_path('module', 'ckeditor_svg') . '/js/plugins/drupalsvg/icons/svg.png',
      ),
    );
  }
}
