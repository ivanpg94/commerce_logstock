<?php

namespace Drupal\commerce_logstock\Form;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;


class LogTableForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'log_table_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pageNo = NULL)
  {
    $pedido = \Drupal::request()->query->get('pedido');
    $producto = \Drupal::request()->query->get('producto');
    $fecha = \Drupal::request()->query->get('fecha');
    $user = \Drupal::request()->query->get('user');

    //$pageNo = 2;
    $header = [
      'producto' => [
        'data' => $this->t('Sku'),
        'sortable' => TRUE,
        'field' => 'producto',
      ],
      'producto_label' => [
        'data' => $this->t('Producto'),
        'sortable' => TRUE,
        'field' => 'producto_label',
      ],
      'pedido' => [
        'data' => $this->t('Pedido'),
        'sortable' => TRUE,
        'field' => 'pedido',
      ],
      'fecha' => [
        'data' => $this->t('Fecha'),
        'sortable' => TRUE,
        'field' => 'fecha',
      ],
      'hora' => [
        'data' => $this->t('Hora'),
        'sortable' => TRUE,
        'field' => 'hora',
      ],
      'stockinicial' => [
        'data' => $this->t('Stock inicial'),
        'sortable' => TRUE,
        'field' => 'stockinicial',
      ],
      'stockfinal' => [
        'data' => $this->t('Stock final'),
        'sortable' => TRUE,
        'field' => 'stockfinal',
      ],
      'user' => [
        'data' => $this->t('Usuario'),
        'sortable' => TRUE,
        'field' => 'user',
      ],
    ];
    $sort_column = \Drupal::request()->query->get('sort_column', 'fechacompleta');
    $sort_direction = \Drupal::request()->query->get('sort', 'desc');
    $query = $this->getLogQuery($pageNo, $sort_column, $sort_direction);

    $this->addSortHeaders($header, $query, $sort_column, $sort_direction);

    foreach ($header as $column => &$column_info) {
      if ($column_info['sortable']) {
        $link = Link::fromTextAndUrl($column_info['data'], $this->generateSortUrl($column_info['field'], $sort_column, $sort_direction));
        $column_info['data'] = $link->toString();
      }
    }

    if ($pageNo != '') {
      $form['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $this->get_log($pageNo, $sort_column, $sort_direction), // Pasa los valores de ordenación aquí
        '#empty' => $this->t('No logs found'),
      ];
    } else {
      $form['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $this->get_log("All"),
        '#empty' => $this->t('No records found'),
      ];
    }
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'commerce_logstock/commerce_logstock';

    $form['#theme'] = 'log_form';
    $form['#prefix'] = '<div class="result_message">';
    $form['#suffix'] = '</div>';
    // $form_state['#no_cache'] = TRUE;
    $form['#cache'] = [
      'max-age' => 0
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {

  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $field = $form_state->getValues();
    $pedido = $field["pedido"];
    $producto = $field["producto"];
    $fecha = $field["fecha"];
    $user = $field["user"];

    $url = \Drupal\Core\Url::fromRoute('commerce_logstock.logmanage')
      ->setRouteParameters(array('pedido' => $pedido, 'fecha' => $fecha, 'producto' => $producto));

    // Redireccionar a la página de resultados con los valores de filtro
    $form_state->setRedirectUrl($url);

    // Volver a construir el formulario con los valores de filtro
    $form_state->setRebuild();
  }
  protected function generateSortLink($column_info, $sort_column, $sort_direction) {
    $newSortDirection = ($sort_direction === 'asc') ? 'desc' : 'asc';

    // Construye la URL con los parámetros de ordenación actualizados
    $url = Url::fromRoute('<current>', [], [
      'query' => [
        'sort_column' => $column_info['field'],
        'sort' => $newSortDirection,
      ],
    ]);

    // Crea el enlace y devuelve el objeto Link
    return Link::fromTextAndUrl($column_info['data'], $url);
  }

  protected function generateSortUrl($field, $currentSortColumn, $currentSortDirection) {
    $newSortDirection = ($field === $currentSortColumn && $currentSortDirection === 'asc') ? 'desc' : 'asc';

    // Obtener valores de filtros actuales
    $pedido = \Drupal::request()->query->get('pedido');
    $producto = \Drupal::request()->query->get('producto');
    $fecha = \Drupal::request()->query->get('fecha');
    $user = \Drupal::request()->query->get('user');

    $url = Url::fromRoute('<current>', [], [
      'query' => [
        'sort_column' => $field,
        'sort' => $newSortDirection,
        'pedido' => $pedido,
        'producto' => $producto,
        'fecha' => $fecha,
        'user' => $user,
      ],
    ]);

    return $url;
  }

  protected function addSortHeaders(&$header, $query, $sort_column, $sort_direction) {
    foreach ($header as $column => &$column_info) {
      if ($column_info['sortable']) {
        $url = $this->generateSortUrl($column_info['field'], $sort_column, $sort_direction);
        $link_text = $column_info['data'];
        if ($column_info['field'] === $sort_column) {
          $link_text .= ($sort_direction === 'asc') ? ' ▲' : ' ▼';
        }
        $link = Link::fromTextAndUrl($link_text, $url)->toString();
        $column_info['data'] = $link;
      }
    }
  }



  protected function getLogQuery($opt, $sort_column = 'fechacompleta', $sort_direction = 'asc') {
    $query = \Drupal::database()->select('commerce_logstock', 'st')
      ->fields('st')
      ->orderBy($sort_column, $sort_direction);

    $pedido = \Drupal::request()->query->get('pedido');
    if (!empty($pedido)) {
      $query->condition('st.pedido', $pedido);
    }

    $user = \Drupal::request()->query->get('user');
    if (!empty($user)) {
      $query->condition('st.user', $user);
    }

    $producto = \Drupal::request()->query->get('producto');
    if (!empty($producto)) {
      $query->condition('st.producto', $producto);
    }

    $fecha = \Drupal::request()->query->get('fecha');
    if (!empty($fecha)) {
      $query->condition('st.fecha', strtotime($fecha));
    }

    $query->orderBy($sort_column, $sort_direction);

    if (is_numeric($opt)) {
      $query->range($opt * 50, 50);
    } else {
      $query->range(0, 50);
    }
    // Ejecuta la consulta y procesa los resultados
    $res = $query->execute()->fetchAll();
    $ret = [];
    foreach ($res as $row) {
      $fechaTimestamp = ($row->fecha);
      $horaTimestamp = ($row->hora);
      $fecha = date('d-m-Y', $fechaTimestamp);
      $hora = date('H:i:s', $horaTimestamp);
      $ret[] = [
        'producto' => [
          'data' => $row->producto,
          'class' => ['mi-clase-producto'],
        ],
        'producto_label' => [
          'data' => $row->producto_label,
          'class' => ['mi-clase-producto-label'],
        ],
        'pedido' => [
          'data' => $row->pedido,
          'class' => ['mi-clase-pedido'],
        ],
        'fecha' => [
          'data' => $fecha,
          'class' => ['mi-clase-fecha'],
        ],
        'hora' => [
          'data' => $hora,
          'class' => ['mi-clase-hora'],
        ],
        'stockinicial' => [
          'data' => $row->stockinicial,
          'class' => ['mi-clase-stockinicial'],
        ],
        'stockfinal' => [
          'data' => $row->stockfinal,
          'class' => ['mi-clase-stockfinal'],
        ],
        'user' => [
          'data' => $row->user,
          'class' => ['mi-clase-user'],
        ],
      ];
    }
    return $ret;
  }



  function get_log($opt, $sort_column, $sort_direction)
  {
    $pedido = "";
    $producto = "";
    $fecha = "";
    $user = "";

    $pedido = \Drupal::request()->query->get('pedido');
    $producto = \Drupal::request()->query->get('producto');
    $fecha = \Drupal::request()->query->get('fecha');
    $user = \Drupal::request()->query->get('user');

    //$sort_column = \Drupal::request()->query->get('sort_column', 'producto_label'); // Default sort column

    if($sort_column || $sort_direction){
      $query = \Drupal::database()->select('commerce_logstock', 'st')
        ->fields('st')
        ->orderBy($sort_column, $sort_direction);

      if (!empty($pedido)) {
        $query->condition('st.pedido', $pedido);
      }

      if (!empty($producto)) {
        $query->condition('st.producto', $producto);
      }

      if (!empty($fecha)) {
        $query->condition('st.fecha', strtotime($fecha));
      }

      if (!empty($user)) {
        // Carga el usuario por nombre de usuario
        $loaded_users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $user]);
        if (!empty($loaded_users)) {
          $loaded_user = reset($loaded_users);
          $query->condition('st.user', $loaded_user->name->value);
        }
      }

      if (is_numeric($opt)) {
        $query->range($opt * 50, 50);
      } else {
        $query->range(0, 50);
      }
      // Ejecuta la consulta y procesa los resultados
      $res = $query->execute()->fetchAll();
      $ret = [];
      foreach ($res as $row) {
        $fechaTimestamp = ($row->fecha);
        $horaTimestamp = ($row->hora);
        $fecha = date('d-m-Y', $fechaTimestamp);
        $hora = date('H:i:s', $horaTimestamp);
        $variations = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->loadByProperties(['sku' => $row->producto]);
        if (!empty($variations)) {
          $variation = reset($variations);

          $product_id = $variation->getProductId();
          $edit_product_link = Link::fromTextAndUrl($this->t($row->producto_label), $variation->toUrl('edit-form'))->toString();
          $edit_product_link_sku = Link::fromTextAndUrl($this->t($row->producto), $variation->toUrl('edit-form'))->toString();
        }
        $order_number = $row->pedido;

        $username = $row->user;
        $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $username]);
        $user = reset($users);
        $user_edit_link = Link::fromTextAndUrl($this->t($username), $user->toUrl('edit-form'))->toString();

        $order_edit_link = Link::fromTextAndUrl($this->t($row->pedido), Url::fromRoute('entity.commerce_order.canonical', ['commerce_order' => $order_number]));
        if($order_number !== 'Actualizacion Stock sin Pedido'){
          $order_edit_link = Link::fromTextAndUrl($this->t($row->pedido), Url::fromRoute('entity.commerce_order.canonical', ['commerce_order' => $order_number]));
        }else{
          $order_edit_link = $row->pedido;
        }
        $ret[] = [
          'producto' => [
            'data' => ['#markup' => $edit_product_link_sku],
            'class' => ['logstock-table-producto'],
          ],
          'producto_label' => [
            'data' => ['#markup' => $edit_product_link],
            'class' => ['logstock-table-producto-label'],
          ],
          'pedido' => [
            'data' => $order_edit_link,
            'class' => ['logstock-table-pedido'],
          ],
          'fecha' => [
            'data' => $fecha,
            'class' => ['logstock-table-fecha'],
          ],
          'hora' => [
            'data' => $hora,
            'class' => ['logstock-table-hora'],
          ],
          'stockinicial' => [
            'data' => $row->stockinicial,
            'class' => ['logstock-table-stockinicial'],
          ],
          'stockfinal' => [
            'data' => $row->stockfinal,
            'class' => ['logstock-table-stockfinal'],
          ],
          'user' => [
            'data' => ['#markup' => $user_edit_link],
            'class' => ['logstock-table-user'],
          ],
        ];
      }
      return $ret;
    }
    if ($opt == "All" && $pedido == "" && $pedido == null && $producto == "" && $producto == null && $fecha == "" && $fecha == null && $user == "" && $user == null) {

      $results = \Drupal::database()->select('commerce_logstock', 'st');

      $results->fields('st');
      $results->range(0, 50);
      $results->orderBy('st.fechacompleta', 'DESC');
      $res = $results->execute()->fetchAll();
      $ret = [];
        //dump("general");
    } else if ($opt == "All"&& $pedido !== null && $pedido !== "") {
      $query = \Drupal::database()->select('commerce_logstock', 'st');

      $query->fields('st');
      $query->orderBy('st.fechacompleta', 'DESC');
      $query->condition('pedido', $pedido);
      $query->range(0, 50);
      $res = $query->execute()->fetchAll();
      $ret = [];

    } else if ($opt == "All"&& $fecha !== null && $fecha !== "") {
      $query = \Drupal::database()->select('commerce_logstock', 'st');

      $query->fields('st');
      $query->orderBy('st.fechacompleta', 'DESC');
      $query->condition('fecha', strtotime($fecha));
      $query->range(0, 50);

      $res = $query->execute()->fetchAll();
      $ret = [];

    }else if ($opt == "All"&& $producto !== null && $producto !== "") {
      $query = \Drupal::database()->select('commerce_logstock', 'st');

      $query->fields('st');
      $query->orderBy('st.fechacompleta', 'DESC');
      $query->range(0, 50);
      $query->condition('producto', $producto);

      $res = $query->execute()->fetchAll();
      $ret = [];
    }else if ($opt == "All" && $user !== null && $user !== "") {
      $query = \Drupal::database()->select('commerce_logstock', 'st');

      $query->fields('st');
      $query->orderBy('st.fechacompleta', 'DESC');
      $query->range(0, 50);
      $query->condition('user', $user);

      $res = $query->execute()->fetchAll();
      $ret = [];
    }else {
      $query = \Drupal::database()->select('commerce_logstock', 'st');

      $query->fields('st');
      $query->range($opt * 50, 50);
      $query->orderBy('st.fechacompleta', 'DESC');
      $res = $query->execute()->fetchAll();
      $ret = [];
    }
    foreach ($res as $row) {
      $fechaTimestamp = ($row->fecha); // Convierte la cadena a timestamp UNIX.
      $horaTimestamp = ($row->hora); // Convierte la cadena a timestamp UNIX.

      $fecha = date('d-m-Y', $fechaTimestamp); // Convierte timestamp UNIX a formato de fecha.
      $hora = date('H:i:s', $horaTimestamp); // Convierte timestamp UNIX a formato de hora.

      $ret[] = [
        'producto' => $row->producto,
        'producto_label' => $row->producto_label,
        'pedido' => $row->pedido,
        'fecha' => $fecha,
        'hora' => $hora,
        'stockinicial' => $row->stockinicial,
        'stockfinal' => $row->stockfinal,
        'user' => $row->user,
      ];
    }
    return $ret;
  }

}
