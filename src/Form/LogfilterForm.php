<?php
namespace Drupal\commerce_logstock\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
/**
 * Provides the form for filter Students.
 */
class LogfilterForm extends FormBase {

  public function getFormId() {
    return 'log_filter_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $currentPedido = \Drupal::request()->query->get('pedido');
    $currentProducto = \Drupal::request()->query->get('producto');
    $currentFecha = \Drupal::request()->query->get('fecha');
    $currentUser = \Drupal::request()->query->get('user');
    $currentTipo = \Drupal::request()->query->get('tipo');


    $form['filters'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Filtrar'),
      '#open'  => true,
      '#attributes' => ['class' => ['logstock-container']],
    ];

    $form['filters']['pedido'] = [
      '#title'         => t('Pedido'),
      '#type'          => 'textfield',
      '#default_value' => $currentPedido,
      '#autocomplete_route_name' => 'commerce_logstock.autocomplete.filter',
      '#attributes' => ['class' => ['logstock-order']],
    ];
    $form['filters']['producto'] = [
      '#title'         => 'Producto',
      '#type'          => 'textfield',
      '#default_value' => $currentProducto,
      '#autocomplete_route_name' => 'commerce_logstock.autocomplete.filterproducto',
      '#attributes' => ['class' => ['logstock-product']],
    ];
    $form['filters']['user'] = [
      '#title'         => 'Usuario',
      '#type'          => 'textfield',
      '#default_value' => $currentUser,
      '#autocomplete_route_name' => 'commerce_logstock.autocomplete.filterproducto',
      '#attributes' => ['class' => ['logstock-product']],
    ];
    $form['filters']['fecha'] = [
      '#title'         => 'Fecha',
      '#type'          => 'search',
      '#default_value' => $currentFecha,
      '#autocomplete_route_name' => 'commerce_logstock.autocomplete.filterfecha',
      '#attributes' => ['class' => ['logstock-date']],
    ];
    $form['filters']['tipo'] = [
      '#title' => t('Tipo'),
      '#type' => 'select',
      '#options' => [
        'todos' => $this->t('Todos'),
        'aumento' => $this->t('Aumento'),
        'decremento' => $this->t('Decremento'),
      ],
      '#default_value' => $currentTipo,
    ];
    $form['filters']['actions'] = [
      '#type'       => 'actions'
    ];
    $form['filters']['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Filtar'),
      '#attributes' => ['class' => ['logstock-submit']],
    ];
    $form['filters']['actions']['reset_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => ['::resetForm'],
      '#attributes' => ['class' => ['logstock-reset']],
    ];


    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function resetForm(array &$form, FormStateInterface $form_state) {
    // Restablecer los valores de los filtros o realizar otras acciones de reinicio.
    // Por ejemplo, puedes redirigir a la página sin ningún filtro aplicado.
    $form_state->setRedirect('commerce_logstock.logmanage');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array & $form, FormStateInterface $form_state) {
    $field = $form_state->getValues();
    $pedido = $field["pedido"];
    $producto = $field["producto"];
    $fecha = $field["fecha"];
    $user = $field["user"];
    $tipo = $field["tipo"];

    $sort_column = \Drupal::request()->query->get('sort_column', 'producto_label');
    $sort_direction = \Drupal::request()->query->get('sort', 'desc');

    $url = \Drupal\Core\Url::fromRoute('commerce_logstock.logmanage')
      ->setRouteParameters([
        'pedido' => $pedido,
        'fecha' => $fecha,
        'producto' => $producto,
        'user' => $user,
        'tipo' => $tipo,
      ])
      ->setOption('query', [
        'sort_column' => $sort_column,
        'sort' => $sort_direction,
      ]);
    $form_state->setRedirectUrl($url);

    $userautocomplete = EntityAutocomplete::extractEntityIdFromAutocompleteInput($form_state->getValue('user'));
    $pedidoautocomplete = EntityAutocomplete::extractEntityIdFromAutocompleteInput($form_state->getValue('pedido'));
    $productoautocomplete = EntityAutocomplete::extractEntityIdFromAutocompleteInput($form_state->getValue('producto'));
    $fechaautocomplete = EntityAutocomplete::extractEntityIdFromAutocompleteInput($form_state->getValue('fecha'));


  }
}
