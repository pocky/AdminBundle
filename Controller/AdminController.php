<?php

/*
 * This file is part of the Black package.
 *
 * (c) Alexandre Balmes <albalmes@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Black\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * AdminController
 *
 * @package Black\Bundle\AdminBundle
 * @author  Alexandre Balmes <albalmes@gmail.com>
 * @license http://opensource.org/licenses/mit-license.php MIT
 *
 * @Route("/admin")
 */
class AdminController extends Controller
{
    /**
     * IndexAction
     * 
     * @Route("/", name="admin_index")
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     * 
     * @return Template
     */
    public function indexAction()
    {
        $personManager      = $this->getPersonManager();

        $countPerson        = $personManager->countAll();

        $persons            = $personManager->getLastPersons();

        return array(
            'countPerson'   => $countPerson,
            'persons'       => $persons,
        );
    }

    /**
     * @Route("/search", name="admin_search_json", defaults={"_format"="json"})
     * @Secure(roles="ROLE_ADMIN")
     * @Method({"GET"})
     * @Template()
     * 
     * @return Template
     */
    public function searchJsonAction()
    {
        $request = $this->get('request');

        if (!$request->isXmlHttpRequest()) {
            return array('response' => array(
                0 => array(
                    'label' => 'error',
                    'value' => 'Request is not valid'
                )
            ));
        }

        $rawDocuments = $this->getPersonManager()->searchPerson($request->query->get('text'));

        $documents = array();

        foreach ($rawDocuments as $document) {
            $documents[$document->getId()] = array(
                'id'            => $document->getId(),
                'name'          => $document->getName(),
            );
        }

        return array(
            'response' => $documents
        );
    }

    /**
     * @Route("/sendmail", name="admin_sendmail")
     * @Secure(roles="ROLE_ADMIN")
     * @Method({"POST"})
     * 
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sendMail()
    {
        $request    = $this->get('request');
        $parameters = $request->request->get('black_engine_contact');

        if ('POST' === $request->getMethod()) {
            $manager      = $this->getPersonManager();
            $repository   = $manager->getRepository();

            $document = $repository->findOneById($parameters['to']);

            if (!$document) {
                throw $this->createNotFoundException('Unable to find Person document.');
            }

            $form = $this->createForm($this->get('black_engine.contact.form.type'), array('id' => $parameters['to']));
            $form->bind($this->getRequest());

            if ($form->isValid()) {
                $this->get('black_engine.mailer')->sendContactMessage($document, $this->getUser(), $parameters);
                $this->get('session')->getFlashbag()->add('success', 'success.admin.admin.mail.send');
            }
        }

        return $this->redirect($this->generateUrl('admin_person_show', array('id' => $parameters['to'])));
    }

    /**
     * @return object
     */
    protected function getPersonManager()
    {
        return $this->get('black_engine.manager.person');
    }
}
