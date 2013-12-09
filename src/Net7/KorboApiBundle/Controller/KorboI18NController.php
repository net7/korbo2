<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zitarosa
 */

namespace Net7\KorboApiBundle\Controller;

use Net7\KorboApiBundle\Entity\Item as Item;
use Doctrine\ORM\AbstractQuery;
use FOS\RestBundle\Controller\FOSRestController;
use Net7\KorboApiBundle\Entity\ItemTranslation;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\AcceptHeader,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Swagger\Annotations as SWG;
use Symfony\Component\Security\Acl\Exception\Exception;

/**
 * Class KorboI18NController
 *
 * @package Net7\KorboApiBundle\Controller
 */
class KorboI18NController extends KorboController {

    private function removeTranslation( $translationClass, $objId, $translationLocale, $translationField)
    {
        $em = $this->getDoctrine()->getManager();

        $translation = $em->getRepository("Net7KorboApiBundle:{$translationClass}")->findOneBy(
            array('locale'    => $translationLocale,
                'field'     => $translationField,
                'object' => $objId ));

        $em->remove($translation);
        $em->flush();
    }

    protected function checkAndSetTranslation($obj, $fieldName, Request $request)
    {
        $ref = new \ReflectionClass(get_class($obj));
        $objClassName = $ref->getShortName();

        $objTranslationClassName = $objClassName . 'Translation';
        $objTranslationClassNameWithNamespace = $ref->getName() . 'Translation';

        if ($request->get($fieldName, false) === false) {

            return;
        }

        $translation = new $objTranslationClassNameWithNamespace($this->contentLanguage, $fieldName, $request->get($fieldName, ''));

        if (($translationIndex = $obj->containsTranslation($translation)) !== false) {
            $this->removeTranslation($objTranslationClassName, $obj->getId(), $this->contentLanguage, $fieldName);
            $obj->addTranslation($translation, $translationIndex);
        } else {
            $obj->addTranslation($translation);
        }
    }
}