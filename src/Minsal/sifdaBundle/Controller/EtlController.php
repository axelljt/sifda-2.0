<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Minsal\sifdaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Description of EtlController
 *
 * @author clainez
 *
 * @Route("/sifda/etl")
 
 */
class EtlController extends Controller{
    
    /**
     * Ejecucion del procedimiento de importacion de datos de PAO.
     *
     * @Route("/pao/{anio}", name="sifda_cargar_pao")
     * @Method("GET")
     */
    public function cargarPAOAction($anio)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('resultado','resultado');
        $anio = 2015;
        $em = $this->getDoctrine()->getEntityManager();
        $sql = "SELECT cargar_data_sidpla(?) as resultado;";
        $query = $em->createNativeQuery($sql, $rsm);
        $query ->setParameter(1, $anio);
        $resultado = $query->getResult();
        $bool = $resultado[0];
        return $this->redirect($this->generateUrl('sifda_ordentrabajo', array('resultado' => $bool)));
    }
    
    
    }
