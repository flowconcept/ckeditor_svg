<?php

/**
 * @file
 * Contains \Drupal\ckeditor_svg\Form\EditorSvgDialog.
 */

namespace Drupal\ckeditor_svg\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides an svg dialog for text editors.
 */
class EditorSvgDialog extends FormBase {

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * Constructs a form object for image dialog.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   The file storage service.
   */
  public function __construct(EntityStorageInterface $file_storage) {
    $this->fileStorage = $file_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editor_svg_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\filter\Entity\FilterFormat $filter_format
   *   The filter format for which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // This form is special, in that the default values do not come from the
    // server side, but from the client side, from a text editor. We must cache
    // this data in form state, because when the form is rebuilt, we will be
    // receiving values from the form, instead of the values from the text
    // editor. If we don't cache it, this data will be lost.
    if (isset($form_state->getUserInput()['editor_object'])) {
      // By convention, the data that the text editor sends to any dialog is in
      // the 'editor_object' key. And the attachment dialog for text editors expects
      // that data to be the attributes for an <img> element.
      $file_element = $form_state->getUserInput()['editor_object'];
      $form_state->set('file_element', $file_element);
      $form_state->setCached(TRUE);
    }
    else {
      // Retrieve the attachment element's attributes from form state.
      $file_element = $form_state->get('file_element') ?: [];
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-svg-dialog-form">';
    $form['#suffix'] = '</div>';

    $editor = editor_load($filter_format->id());

    // Construct strings to use in the upload validators.
    $config = array();
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['drupalsvg'])) {
      $config = $settings['plugins']['drupalsvg'];
    }

    $existing_file = isset($file_element['data-entity-uuid']) ? \Drupal::service('entity_type.manager')->loadEntityByUuid('file', $file_element['data-entity-uuid']) : NULL;
    $fid = $existing_file ? $existing_file->id() : NULL;

    $form['fid'] = array(
      '#field_name' => 'drupalsvg',
      '#title' => $this->t('File'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://inline-svg',
      '#default_value' => $fid ? array($fid) : NULL,
      '#upload_validators' => array(
        'file_validate_extensions' => array('svg'),
        'file_validate_size' => array(),
      ),
      '#required' => TRUE,
    );

    $form['width'] = array(
      '#title' => $this->t('Image width (%)'),
      '#type' => 'number',
      '#default_value' => 100,
        '#size' => 3,
      '#min' => 1,
      '#max' => 100,
      '#required' => TRUE,
    );

    $form['alt'] = array(
        '#title' => $this->t('Alternative Bildbeschreibung'),
        '#type' => 'textfield',
        '#required' => TRUE,
    );

    $form['align'] = array(
        '#title' => $this->t('Ausrichten'),
        '#type' => 'radios',
        '#options' => array(
            '' => 'Keine',
            'align-left' => 'Links',
            'align-center' => 'Zentriert',
            'align-right' => 'Rechts',
        ),
        '#attributes' => array(
            'class' => array('container-inline'),
        ),
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['save_modal'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => array(),
      '#ajax' => array(
        'callback' => '::submitForm',
        'event' => 'click',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // Convert any uploaded files from the FID values to data-entity-uuid
    // attributes and set data-entity-type to 'file'.
    $fid = $form_state->getValue(array('fid', 0));
    if (!empty($fid)) {
      $file = $this->fileStorage->load($fid);
      // Setting file status to 'permanent', so it wont be deleted on next cron run.
      $file->setPermanent();
      $file->save();

      $file_url = file_create_url($file->getFileUri());
      // Transform absolute svg URLs to relative URLs: prevent problems
      // on multisite set-ups and prevent mixed content errors.
      $file_url = file_url_transform_relative($file_url);
      $form_state->setValue(array('attributes', 'src'), $file_url);
    }

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-svg-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }
}
