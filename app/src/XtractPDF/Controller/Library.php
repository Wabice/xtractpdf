<?php

namespace XtractPDF\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use XtractPDF\Core\Controller;
use XtractPDF\Model;

/**
 * Library Controller
 */
class Library extends Controller
{
    /**
     * @var XtractPDF\Library\DocumentMgr
     */
    private $docMgr;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var XtractPDF\Library\BuilderBag
     */
    private $builders;

    /**
     * @var XtractPDF\Library\DocumentAPIHandler
     */
    private $apiHandler;

    /**
     * @var XtractPDF\DocRenderer\ArrayRenderer
     */
    private $arrayRenderer;

    /**
     * @var array
     */
    private $viewData;

    // --------------------------------------------------------------

    /**
     * Constructor - Set Initial State
     */
    public function __construct()
    {
        $this->viewData = array();
    }

    // --------------------------------------------------------------

    /**
     * Set the routes
     *
     * Be sure to only set routes in here, and load all other resources
     * in self::init() for performance reasons
     *
     * Run $app->get(), $app->match(), etc.. in this method
     *
     * @param Silex\Application $app
     */
    protected function setRoutes(ControllerCollection $routes)
    {
        $routes->get('/',                 array($this, 'indexAction'));
        $routes->get('/library',          array($this, 'libraryAction'));
        $routes->get('/library/{uniqId}', array($this, 'singleAction'));
        
        $routes->post('/library',          array($this, 'uploadAction'));
        $routes->post('/library/{uniqId}', array($this, 'updateAction'));
    }

    // --------------------------------------------------------------

    /**
     * The init method is run upon the controller executing
     *
     * Pull libraries form the DiC here in child classes
     */
    protected function init(Application $app)
    {        
        //Load dependencies
        $this->twig          = $app['twig'];        
        $this->docMgr        = $app['doc_mgr'];
        $this->builders      = $app['builders'];
        $this->arrayRenderer = $app['renderers']->get('array');
        $this->apiHandler    = $app['api_builder'];

        //Set the page class for twig views
        $this->viewData['page_class'] = 'workspace';
    }

    // --------------------------------------------------------------

    /**
     * GET /
     */
    public function indexAction()
    {
        return $this->twig->render('pages/library.html.twig', $this->viewData);
    }

    // --------------------------------------------------------------

    /**
     * GET /library
     */
    public function libraryAction()
    {
        if ($this->clientExpects('json')) {

            //Determine limit, offset, search params

            //Get list of items from the docMgr

            //Return it as JSON

        }
        else { //Do HTML
            return $this->twig->render('pages/library.html.twig', $this->viewData);
        }
    }

    // --------------------------------------------------------------
    
    /**
     * GET /library/{uniqId}
     *
     * @param $uniqId  The identifier for a document object
     */
    public function singleAction($uniqId)
    {
        //Get the item from the database (or 404 if it doesn't exist)        
        $doc = $this->docMgr->getDocument($uniqId);

        //If not found, abort
        if ( ! $doc) {
            return $this->abort(404, 'Document Not Found');
        }

        //Display options - for building views with JS and HTML
        $dispOptions = array(
            'availSecTypes'  => Model\DocumentSection::getAllowedTypes(),
            'biblioMetaDisp' => Model\DocumentBiblioMeta::getDispInfo()            
        );      

        //If JSON, return the document
        if ($this->clientExpects('json')) {

            $jsonData = array();
            $jsonData['document'] = $this->arrayRenderer->render($doc);

            if ($this->getQueryParams('disp_opts')) {
                $jsonData['dispOptions'] = $dispOptions;
            }

            return $this->json($jsonData);
        }
        else { //Load the interface

            //Add doc to the viewData
            $this->viewData['doc']         = $doc;
            $this->viewData['docUrl']      = $this->getCurrentUrl();
            $this->viewData['dispOptions'] = $dispOptions;

            return $this->twig->render('pages/library-single.html.twig', $this->viewData);
        }
    }    

    // --------------------------------------------------------------
    
    /**
     * POST /library
     */
    public function uploadAction()
    {
        //Upload the document

        //Persist it with the persister

        //Get the document id
        $uniqId = 'whatever_the_uploaded_and_saved_doc_was';

        //Return a response
        if ($this->clientExpects('json')) {
            //Return JSON notification that everything went well and URL pointer to the document

        }
        else { //Do HTML redirect
            return $this->redirect('/single/' . $uniqId);
        }        
    }

    // --------------------------------------------------------------

    /**
     * POST /library/{uniqId}
     *
     * @param $uniqId  The identifier for a document object
     */
    public function updateAction($uniqId)
    {
        //Check if document exists, otherwise render a 404
        $doc = $this->docMgr->getDocument($uniqId);

        //If not found, abort
        if ( ! $doc) {
            return $this->abort(404, 'Document Not Found');
        }

        //Check for expectd POST data
        $postDoc = $this->getPostParams('document');

        if ( ! $postDoc) {
            return $this->abort(400, 'Missing required request parameters');
        }

        //Set document data from POST request
        $doc = $this->apiHandler->build($postDoc, $doc);

        //Persist document
        $this->docMgr->updateDocument($doc);

        //Return a response
        if ($this->clientExpects('json')) {

            //Return JSON notification that everything went well and URL pointer to the document
            return $this->json(array(
                'message' => 'Updated Document',
                'docUrl'  => $this->getCurrentUrl()
            ));
        }
        else { //Do HTML redirect
            return $this->redirect('/single/' . $uniqId);
        }           
    }
}    

/* EOF: Library.php */