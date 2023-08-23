<?php

namespace Drupal\commerce_logstock\EventSubscriber;

use Drupal;
use Drupal\commerce_stock_local\Event\LocalStockTransactionEvent;
use Drupal\commerce_stock_local\Event\LocalStockTransactionEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Database\Connection;
use Drupal\edit_in_place_field\Form;

class StockTransactionEventSubscriber implements EventSubscriberInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new StockTransactionEventSubscriber object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      LocalStockTransactionEvents::LOCAL_STOCK_TRANSACTION_INSERT => 'onStockTransactionInsert',
    ];
  }

  /**
   * Handles the stock transaction insert event.
   *
   * @param \Drupal\commerce_stock_local\Event\LocalStockTransactionEvent $event
   *   The order event.
   */
  public function onStockTransactionInsert(LocalStockTransactionEvent $event) {
    $parameter = \Drupal::routeMatch()->getParameter('commerce_product');
    $order = \Drupal::routeMatch()->getParameter('commerce_order');
    $fecha = date('Y-m-d');
    $hora = date('H:i:s');
    $fechaCompleta = date('Y-m-d H:i:s');
    $current_user_id = Drupal::currentUser()->id();
    $user = \Drupal\user\Entity\User::load($current_user_id)->name->value;
    if ($order) {
      $pedido = $order->order_id->value;

      $stockService = \Drupal::service('commerce_stock.service_manager');
      $logstockData = [];

      foreach ($order->getItems() as $pedido_item) {
        $product_variation = $pedido_item->getPurchasedEntity();
        $sku = $product_variation->getSku();
        $label = $product_variation->getTitle();

        $stock_transaction= $pedido_item->getQuantity();
        $stock = $stockService->getStockLevel($product_variation);
        $stockinicial = $stock + $stock_transaction;
        $stock_final = $stock;

        // Comprueba si ya existe un registro con los mismos valores.
        $existing = $this->database->select('commerce_logstock', 'cls')
          ->fields('cls', ['producto', 'pedido'])
          ->condition('producto', $sku)
          ->condition('pedido', $pedido)
          ->execute()
          ->fetchAssoc();

        if (!$existing) {
          $logstockData[] = [
            'producto' => $sku,
            'producto_label' =>$label,
            'pedido' => $pedido,
            'fecha' => strtotime($fecha),
            'stockinicial' => $stockinicial,
            'stockfinal' => $stock_final,
            'fechacompleta' => strtotime($fechaCompleta),
            'hora' => strtotime($hora),
            'user' => $user,
          ];
        }
      }

      if (!empty($logstockData)) {
        foreach ($logstockData as $values) {
          $this->database->insert('commerce_logstock')
            ->fields([
              'producto',
              'pedido',
              'producto_label',
              'fecha',
              'stockinicial',
              'stockfinal',
              'fechacompleta',
              'hora',
              'user',
            ])
            ->values($values)
            ->execute();
        }
      }
    }else if($parameter){
      $pedido = 'Actualizacion Stock sin Pedido';
      $stockService = \Drupal::service('commerce_stock.service_manager');

      $id = $parameter->variations->entity->variation_id->value;
      $variation = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->load($id);
      $stockactual = $stockService->getStockLevel($variation);
      $stock_transaction = $variation->field_stock->value;

      $stockInicial = $stockactual - $stock_transaction; // Cambia esto al valor real.
      $stockFinal = $stockactual;
      $sku = $parameter->variations->entity->sku->value;
      $label = $parameter->variations->entity->title->value;

      // Insertar los datos en la tabla 'commerce_logstock'.
      $this->database->insert('commerce_logstock')
        ->fields([
          'producto' => $sku,
          'producto_label' =>$label,
          'pedido' => $pedido,
          'fecha' => strtotime($fecha),
          'stockinicial' => $stockInicial,
          'stockfinal' => $stockFinal,
          'fechacompleta' => strtotime($fechaCompleta),
          'hora' => strtotime($hora),
          'user' => $user,
        ])
        ->execute();
    }else{
      $data = $event->getEntity();

      $id_variacion = $data->variation_id->value;
      $variation = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->load($id_variacion);
      $product_id = $variation->product_id->getValue()[0]['target_id'];

      $product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load($product_id);

      $pedido = 'Actualizacion Stock sin Pedido';
      $id = $product->variations->entity->variation_id->value;
      $stockService = \Drupal::service('commerce_stock.service_manager');

      $variation = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->load($id);
      $stockactual = $stockService->getStockLevel($variation);
      $stock_transaction = $variation->field_stock->value;

      $stockInicial = $stockactual - $stock_transaction; // Cambia esto al valor real.
      $stockFinal = $stockactual;
      $sku = $product->variations->entity->sku->value;
      $label = $product->variations->entity->title->value;
      $fecha = date('Y-m-d');
      $hora = date('H:i:s');
      $fechaCompleta = date('Y-m-d H:i:s');
      $database = \Drupal::database();
      $query = $database->insert('commerce_logstock')
        ->fields([
          'producto' => $sku,
          'producto_label' =>$label,
          'pedido' => $pedido,
          'fecha' => strtotime($fecha),
          'stockinicial' => $stockInicial,
          'stockfinal' => $stockFinal,
          'fechacompleta' => strtotime($fechaCompleta),
          'hora' => strtotime($hora),
          'user' => $user,
        ])
        ->execute();
    }
  }
}
