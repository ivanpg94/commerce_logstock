<?php

namespace Drupal\commerce_logstock\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;

class LogController extends ControllerBase
{

    public function pedidoAutocomplete(Request $request) {
      $results = [];
      $input = $request->query->get('q');
      // Compruebo si existe el parámetro.
      if (!$input) {
        return new JsonResponse($results);
      }

      //$input ='0';
      //dump($input);

      $input = Xss::filter($input);

      $query = \Drupal::database()->select('commerce_logstock', 'st');
      $query->fields('st');
      $query->condition('pedido', $input.'%', 'LIKE');
      //$query->range(0, 10);
      $query->distinct();
      $ids = $query->execute()->fetchAll();
   //   dump($ids);

      //$nodes = $ids ? $this->nodeStroage->loadMultiple($ids) : [];
      $results = $ids;

      foreach ($ids as $row) {
        $ret[]= ['pedido' => $row->pedido,];
      }
      for ($i =0; $i<(count($ret)-1); $i++){
        $results[$i]=$ret[$i]['pedido'];
      }

      array_pop($results);

      $results = array_unique($results);
      //dump($results);

      //    $results[] = [
    //               'value' => EntityAutocomplete::getEntityLabels([$results]),
    //             ];

      return new JsonResponse($results);
    }

  public function productoAutocomplete(Request $request) {
    $results = [];
    $input = $request->query->get('q');
    // Compruebo si existe el parámetro.
    if (!$input) {
      return new JsonResponse($results);
    }
   // dump($input);

  //  $input ='0';
    //dump($input);

    $input = Xss::filter($input);

    $query = \Drupal::database()->select('commerce_logstock', 'st');
    $query->fields('st');
    $query->condition('producto', $input.'%', 'LIKE');
    //$query->range(0, 10);
    $query->distinct();
    $ids = $query->execute()->fetchAll();
    //$nodes = $ids ? $this->nodeStroage->loadMultiple($ids) : [];
    $results = $ids;

    foreach ($ids as $row) {
      $ret[]= ['producto' => $row->producto,];
    }
    for ($i =0; $i<(count($ret)-1); $i++){
      $results[$i]=$ret[$i]['producto'];
    }

    array_pop($results);

    $producto = array_unique($results);


    return new JsonResponse($producto);
  }

  public function fechaAutocomplete(Request $request) {
    $results = [];
    $input = $request->query->get('q');
    // Compruebo si existe el parámetro.
    if (!$input) {
      return new JsonResponse($results);
    }
  //  dump($input);

   // $input ='2';
    $input = Xss::filter($input);

    $query = \Drupal::database()->select('commerce_logstock', 'st');
    $query->fields('st');
    $query->condition('fecha', $input.'%', 'LIKE');
    //$query->range(0, 10);
    $query->distinct();
    $ids = $query->execute()->fetchAll();
    //$nodes = $ids ? $this->nodeStroage->loadMultiple($ids) : [];
    $results = $ids;

    foreach ($ids as $row) {
      $ret[]= ['fecha' => $row->fecha,];
    }
    for ($i =0; $i<(count($ret)-1); $i++){
      $results[$i]=$ret[$i]['fecha'];
    }

    array_pop($results);
    $fecha = array_unique($results);

    //    $results[] = [
    //               'value' => EntityAutocomplete::getEntityLabels([$results]),
    //             ];
    return new JsonResponse($fecha);
  }

  public function manageLogs()
  {

    $form['form2'] = \Drupal::formBuilder()->getForm('Drupal\commerce_logstock\Form\LogfilterForm');

    $render_array = \Drupal::formBuilder()->getForm('Drupal\commerce_logstock\Form\LogTableForm','All');
    $form['form1'] = $render_array;
    $form['form']['#suffix'] = '<div class="pagination">'.getPager().'</div>';
    return $form;
  }
  public function tablePaginationAjax($no){
    $response = new AjaxResponse();
    $render_array = \Drupal::formBuilder()->getForm('Drupal\commerce_logstock\Form\LogTableForm',$no);
    $response->addCommand(new HtmlCommand('.result_message','' ));
    $response->addCommand(new \Drupal\Core\Ajax\AppendCommand('.result_message', $render_array));
    return $response;
  }

}
