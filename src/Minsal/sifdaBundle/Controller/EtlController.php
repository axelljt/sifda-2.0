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
use \Minsal\sifdaBundle\Entity\SifdaTipoServicio;
use \Minsal\sifdaBundle\Entity\SifdaSolicitudServicio;
use \Minsal\sifdaBundle\Entity\SifdaOrdenTrabajo;
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
    
    /**
     * Ejecucion del procedimiento de Generacion automatica de ordenes y solicitudes.
     *
     * @Route("/paoSolOrd/{depest}", name="cargar_solicitudes_pao")
     * @Method("GET")
     */
    public function cargarSoliOrdenAction($depest) {
        try {
            $em = $this->getDoctrine()->getManager();
            $objDepEst = $em->getRepository('MinsalsifdaBundle:CtlDependenciaEstablecimiento')->find($depest);
            $lstTiposServicio = $em->getRepository('MinsalsifdaBundle:SifdaTipoServicio')->findBy(array('idDependenciaEstablecimiento' => $objDepEst->getId()));
            foreach ($lstTiposServicio as $tipoServicio) {
//            $tipoServicio = new SifdaTipoServicio();
                $solicitud = new SifdaSolicitudServicio();
                $actividadPAO = new \Minsal\sifdaBundle\Entity\SidplaActividad();
                $actividadPAO = $tipoServicio->getIdActividad();
                $solicitud->setIdDependenciaEstablecimiento($objDepEst);
                $solicitud->setDescripcion($tipoServicio->getDescripcion());
                $solicitud->setFechaRecepcion(new \DateTime());
                $objMedioSolicita = $em->getRepository('MinsalsifdaBundle:CatalogoDetalle')->find(6);
                $solicitud->setIdMedioSolicita($objMedioSolicita);
                $objEstado = $em->getRepository('MinsalsifdaBundle:CatalogoDetalle')->find(2);
                $solicitud->setIdEstado($objEstado);
                $solicitud->setIdTipoServicio($tipoServicio);
                $em->persist($solicitud);
                $em->flush();
                $lstCicloVida = $em->getRepository('MinsalsifdaBundle:SifdaRutaCicloVida')->findBy(array('idTipoServicio' => $tipoServicio->getId(), 'jerarquia' => 1, 'idEtapa' => null));
                foreach ($lstCicloVida as $etapa) {
//                $etapa = new \Minsal\sifdaBundle\Entity\SifdaRutaCicloVida();
                    $orden = new SifdaOrdenTrabajo();
                    $orden->setIdEtapa($etapa->getId());
                    $orden->setDescripcion($etapa->getDescripcion());
                    $orden->setCodigo($this->generarCodigoOrden($objDepEst));
                    $orden->setFechaCreacion(new \DateTime());
                    $orden->setIdDependenciaEstablecimiento($objDepEst);
                    $orden->setIdEstado($objEstado);
                    $orden->setIdSolicitudServicio($solicitud);
                    $objPrioridad = $em->getRepository('MinsalsifdaBundle:CatalogoDetalle')->find(9);
                    $orden->setIdPrioridad($objPrioridad);
                    $em->persist($orden);
                    $em->flush();
                    $equipo = new \Minsal\sifdaBundle\Entity\SifdaEquipoTrabajo();
                    $equipo->setIdOrdenTrabajo($orden);
                    $equipo->setIdEmpleado($actividadPAO->getIdEmpleado());
                    $equipo->setResponsableEquipo(true);
                    $em->persist($equipo);
                    $em->flush();
                }
            }

            return $this->redirect($this->generateUrl('sifda_ordentrabajo'));
        } catch (Exception $ex) {
            $ex;
            $ex;
        }
    }

    /** 
     * Metodo que sirve para generar el codigo de la orden de trabajo
     * 
     * @param \Minsal\sifdaBundle\Entity\CtlDependenciaEstablecimiento $idDependenciaEstablecimiento
     * 
     * @return string
     */
    public function generarCodigoOrden(\Minsal\sifdaBundle\Entity\CtlDependenciaEstablecimiento $idDependenciaEstablecimiento) 
    {
        $codigo = "";
        $dependencia = $idDependenciaEstablecimiento->getIdDependencia()->getNombre();
        $establecimiento = $idDependenciaEstablecimiento->getIdEstablecimiento()->getNombre();
        
        $codigo.= substr($establecimiento, 0, 3);
        $codigo.= substr($dependencia, 0, 2);
        $codigo = strtoupper($codigo);        
        
        $fechaActual = new \DateTime();
        $dql = "SELECT count(u.codigo) cantidad
			  FROM MinsalsifdaBundle:SifdaOrdenTrabajo u
			  WHERE u.codigo LIKE :codigo";
                  
        $em = $this->getDoctrine()->getManager();
        $cantidadCodigos= $em->createQuery($dql)
                             ->setParameter(':codigo', $codigo.'___'.$fechaActual->format('y'))
                             ->getResult();
        $cantidad = $cantidadCodigos[0]['cantidad'] + 1;
        
        switch ($cantidad){
            case ($cantidad < 10):
                $codigo.= "00".$cantidad;
                break;
            case ($cantidad >= 10 and $cantidad < 100):
                $codigo.= "0".$cantidad;
                break;
            default:
                $codigo.= $cantidad;
                break;
        }
        
        $codigo.= $fechaActual->format('y');
        
        return $codigo;
    }
    
    
    }

