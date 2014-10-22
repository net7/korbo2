<?php
namespace Net7\KorboApiBundle\Command;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class MergeItemsCommand
 *
 * @package Net7\KorboApiBundle\Command
 */
class MergeItemsCommand extends ContainerAwareCommand
{

    private $connection;

    /**
     * Setup function
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('korbo:merge-items')
            ->addArgument('basket-id',              InputArgument::REQUIRED, 'Basket ID')
            ->addArgument('id-to-delete',           InputArgument::REQUIRED, 'Id of the Item id you want to delete')
            ->addArgument('id-to-merge',            InputArgument::REQUIRED, 'Id of the item that will replace id-to-delete')
            ->addArgument('dl-get-api',             InputArgument::REQUIRED, 'DL Api needed to retrieve the list of entities to modify')
            ->addArgument('dl-merge-api',           InputArgument::REQUIRED, 'DL Api needed to update entities')
            ->addArgument('as-sesame-api-base-url', InputArgument::REQUIRED, 'AS Api needed to retrieve the list of annotations to modify')


            //->addOption('country', 'c', InputOption::VALUE_OPTIONAL, 'Country', '')

        ;
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract class is not implemented
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->setUpEnvironment($input);
        } catch (Exception $e) {
            throw $e;
        }

        $output->writeln('Executing merge...');

        if (($message = $this->checkItemsConsistency()) !== true) {
            //$this->logger->info($message);
            throw new Exception($message);
        }

        // call DL API -- returns the list of elements to merge
        $elementListUrl = $this->dlGetApiUrl . '?uri=' . $this->itemToDelete->getUri();
        $elementsToMergeRequest = $this->doApiRequest($elementListUrl, true);

        // something went wrong (not reachable, api error)
        if ($elementsToMergeRequest === false) {
            throw new Exception('Something went wrong...DL retrieving API not reachable');
        }

        $elementsToMerge = json_decode($elementsToMergeRequest, true);

        $output->writeln("\nThe following elements containing the uri {$this->itemToDelete->getUri()} will be modified: \n");
        // display the modification to make
        foreach ($elementsToMerge as $element) {
            foreach ($element['fields'] as $field => $values) {
                if ($values['uri'] == $this->itemToDelete->getUri()) {
                    $output->writeln($element['id'] . ' ' . $element['slug']);
                    $output->writeln($field . ' : ' . $values['label']);
                }
            }
            $output->writeln("");
        }

        // call AS api -- returns the list of annotations to modify
        $annotations = $this->getAnnotations();
        if (count($annotations) > 0) {
            $output->writeln("\nThe the uri {$this->itemToDelete->getUri()} is present in the following annotations: \n");
        }
        else {
            $output->writeln("\nYou are lucky ... no annotations to update! \n");
        }

        foreach ($annotations as $annotation) {
            $output->writeln($annotation['s'] . '  --  ' . $annotation['p'] . '  --  ' . $this->itemToDelete->getLabelTranslated() . "\n");
        }


        // recupero la label...di default assumiamo sia in inglese...
        // faccio update su korbo
        $elementUpdateUrl = $this->dlMergeApiUrl . '?uri1=' . $this->itemToDelete->getUri() . '&uri2=' . $this->itemToMerge->getUri() . '&label2=' . $this->itemToMerge->getLabelTranslated();

        $updateQuery = json_decode($this->doApiRequest($elementUpdateUrl), true);

        // there was an error calling the update api
        if ($updateQuery['error'] != false) {
            throw new Exception($updateQuery['error']);
        }

        // updating the annotations
        $output->writeln("Updating the annotations... \n");
        $updateAnnotationResponse = $this->updateAnnotations($annotations);
        if ($updateAnnotationResponse === false) {
            throw new Exception('Something went wrong...AS API not reachable or got error');
        }


        // rimuovo l'item da cancellare su korbo
        $output->writeln('Deleting item with ID ' . $this->itemToDelete->getId());
        $this->em->remove($this->itemToDelete);
        $this->em->flush();

        $output->writeln("Everythig went fine!");

    }


    private function updateAnnotations($annotations) {
        //$baseUrl = "http://demo-cloud.as.thepund.it:8080";

        $itemsTypesForQuery = array();
        foreach ($this->itemToMerge->getTypesArray() as $type) {
            $itemsTypesForQuery[] = '<' . $this->itemToMerge->getUri() . '> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <' .  $type . '>';
        }
        $itemsTypesForQuery = implode(' . ', $itemsTypesForQuery);

        foreach ($annotations as $annotation) {
            // TODO: remove...insert in configuration
            //$query = "WITH <http://purl.org/pundit/demo-cloud-server/graph/annotationGraph-{$annotation['annotationId']}> " .
            $query = "WITH <http://purl.org/pundit/as/graph/annotationGraph-{$annotation['annotationId']}> " .
                     "DELETE { ?s ?p <{$this->itemToDelete->getUri()}> } " .
                     "INSERT { ?s ?p <{$this->itemToMerge->getUri()}> } " .
                     "WHERE{ ?s ?p <{$this->itemToDelete->getUri()}> }" ;



        //$queryInsert = 'WITH <http://purl.org/pundit/demo-cloud-server/graph/itemsGraph-' . $annotation["annotationId"] . '> ' .
        $queryInsert = 'WITH <http://purl.org/pundit/as/graph/itemsGraph-' . $annotation["annotationId"] . '> ' .
            'DELETE { <' . $this->itemToDelete->getUri() . '> ?p ?o } ' .
            'INSERT { <' . $this->itemToMerge->getUri() . '>  <http://www.w3.org/2000/01/rdf-schema#label> "' . $this->itemToMerge->getLabelTranslated() .  '" . ' .
            '<' . $this->itemToMerge->getUri() .  '>  <http://www.w3.org/2004/02/skos/core#label> "' . $this->itemToMerge->getLabelTranslated() . '" . ' .
            '<' . $this->itemToMerge->getUri() . '>  <http://purl.org/dc/elements/1.1/description> "' .  preg_replace( "/\r|\n/", "", $this->itemToMerge->getAbstractTranslated() ) .  '" . ' .
            '<' . $this->itemToMerge->getUri() . '>  <http://xmlns.com/foaf/0.1/depiction> "'  .  $this->itemToMerge->getDepiction() . '" . ' .
            $itemsTypesForQuery .
            '} ' .
            'WHERE{ <' . $this->itemToDelete->getUri() . '> ?p ?o }' ;


            $response1 = $this->doPostApiRequest($this->asSesameBaseApiUrl . "/openrdf-workbench/repositories/pundit/update",  http_build_query(array('update' => $query, 'queryLn' => "SPARQL")));

            if ($response1 === false) {
                return false;
            }
            $response2 = $this->doPostApiRequest($this->asSesameBaseApiUrl . "/openrdf-workbench/repositories/pundit/update",  http_build_query(array('update' => $queryInsert, 'queryLn' => "SPARQL")));

            if ($response2 === false) {
                return false;
            }
        }

        return true;

    }

    /**
    $query = "WITH <http://purl.org/pundit/demo-cloud-server/graph/annotationGraph-deb77863> " .
    "DELETE { ?s ?p <http://rdf.freebase.com/ns/en.silvio_berlusconi222> } " .
    "INSERT { ?s ?p <http://rdf.freebase.com/ns/en.silvio_berlusconi1> } " .
    "WHERE{ ?s ?p <http://rdf.freebase.com/ns/en.silvio_berlusconi222> }" ;



    $queryInsert = 'WITH <http://purl.org/pundit/demo-cloud-server/graph/itemsGraph-deb77863> ' .
    'DELETE { <http://rdf.freebase.com/ns/en.silvio_berlusconi1> ?p ?o } ' .
    'INSERT { <http://rdf.freebase.com/ns/en.silvio_berlusconi1>  <http://www.w3.org/2000/01/rdf-schema#label> "aaaaaa" . ' .
    '<http://rdf.freebase.com/ns/en.silvio_berlusconi1>  <http://www.w3.org/2004/02/skos/core#label> "aaaaa" . ' .
    '<http://rdf.freebase.com/ns/en.silvio_berlusconi1>  <http://purl.org/dc/elements/1.1/description> "aaaaa" . ' .
    '<http://rdf.freebase.com/ns/en.silvio_berlusconi1>  <http://xmlns.com/foaf/0.1/depiction> "aaaaa" . ' .
    '<http://rdf.freebase.com/ns/en.silvio_berlusconi1>  <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://purl.org/pundit/ont/ao#fragment-text> ' .
    '} ' .
    'WHERE{ <http://rdf.freebase.com/ns/en.silvio_berlusconi1> ?p ?o }' ;

     */

    private function getAnnotations() {
        //TODO: sostituire con uri da eliminare....
        //$uri = 'http://purl.org/net7/dev.korbo2/item/41';
        $uri = $this->itemToDelete->getUri();

        $annotations = array();

        $query = <<<EOT
select ?s ?p ?o ?c
where {
GRAPH ?c {
?s ?p <{$uri}>
}
}

EOT;

        //$baseUrl = "http://demo-cloud.as.thepund.it:8080";

        $annotationList = json_decode($this->doApiRequest($this->asSesameBaseApiUrl . "/openrdf-sesame/repositories/pundit?query=" . urlencode($query) . '&queryLn=SPARQL'), true);
        //echo $this->asSesameBaseApiUrl . "/openrdf-sesame/repositories/pundit?query=" . urlencode($query) . '&queryLn=SPARQL';die;
        if ($annotationList == '') return array();

        foreach ($annotationList['results']['bindings'] as $annotation) {
            $annotationId = substr(strrchr($annotation['c']['value'], "-"), 1);
            $annotationMetadata = json_decode($this->doApiRequest($this->asSesameBaseApiUrl . "/annotationserver/api/open/annotations/" . $annotationId), true);

            //print_r($annotationMetadata);die;
            $predicateKey = '';
            $annotationXpointer = '';
            foreach ($annotationMetadata['graph'] as $key => $value) {
                $annotationXpointer = $key;
                foreach ($value as $predValue => $val) {
                    $predicateKey = $predValue;
                    break;
                }
            }

            $subject = '';
            $predicate = '';

            foreach ($annotationMetadata['items'] as $key => $element) {
                if (strpos($key, "xpointer") !== false) {
                    $subject = $element['http://purl.org/dc/elements/1.1/description'][0]['value'];
                }

                if ($key == $predicateKey) {
                    $predicate = $element['http://www.w3.org/2000/01/rdf-schema#label'][0]['value'];
                }
            }

            //print_r($annotationMetadata);die;
            $annotations[] = array("s" => $subject, 'p' => $predicate,
                'xpointer' => $annotationXpointer, 'context' => $annotation['c']['value'],
                'predicateUri' => $predicateKey, 'annotationId' => $annotationId,
                'page-context' => $annotationMetadata['metadata']["http://purl.org/pundit/as/annotation/" . $annotationId]['http://purl.org/pundit/ont/ao#hasPageContext'][0]['value']);


        }

        return $annotations;
    }


    private function doApiRequest($url, $returnBooleanWhenErrorOccurs = false) {
        $contentType = 'application/json';

        $request = curl_init();
        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_HTTPHEADER, array("Accept: {$contentType}"));
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, 1);

        $response = curl_exec($request);
        $error = curl_error($request);
        $http_code = curl_getinfo($request, CURLINFO_HTTP_CODE);

        if (!curl_errno($request)) {
            if ($returnBooleanWhenErrorOccurs === true) {
                if (json_decode($response, true) == '') return false;
            }
            $result = $response;
        } else {
            if ($returnBooleanWhenErrorOccurs === true) return false;
            $result = $error;
        }

        curl_close($request);

        return $result;
    }

    private function doPostApiRequest($url, $data) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/x-www-form-urlencoded"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
                    $data);

        // receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);
        $error =  curl_error($ch);

        curl_close ($ch);

        // further processing ....
        if ($server_output == "OK") {
            return true;
        } else {
            return false;
        }
    }

    private function setUpEnvironment(InputInterface $input) {

        $basketId = $input->getArgument('basket-id');
        $itemIdToDelete = $input->getArgument('id-to-delete');
        $itemIdToMerge = $input->getArgument('id-to-merge');
        $basketId = $input->getArgument('basket-id');
        $this->dlGetApiUrl = $input->getArgument('dl-get-api');
        $this->dlMergeApiUrl = $input->getArgument('dl-merge-api');
        $this->asSesameBaseApiUrl = $input->getArgument('as-sesame-api-base-url');


        $this->em = $this->getContainer()->get('doctrine')->getEntityManager();
        $this->logger = $this->getContainer()->get('logger');

        $this->itemToDelete = $this->em->find("Net7KorboApiBundle:Item", $itemIdToDelete);
        $this->itemToMerge  = $this->em->find("Net7KorboApiBundle:Item", $itemIdToMerge);

        if ($this->itemToDelete == null) {
            throw new Exception("Item " . $itemIdToDelete . ' does not exist');
            return;
        }
        if ($this->itemToDelete == null) {
            throw new Exception("Item " . $itemIdToMerge . ' does not exist');
            return;
        }

        $this->itemToMerge->setTranslatableLocale("en");
        $this->itemToDelete->setTranslatableLocale("en");
        $this->itemToDelete->setBaseItemUri($this->getContainer()->getParameter('korbo_base_purl_uri'));
        $this->itemToMerge->setBaseItemUri($this->getContainer()->getParameter('korbo_base_purl_uri'));


        $this->basket       = $this->em->find("Net7KorboApiBundle:Basket", $basketId);
    }


    private function checkItemsConsistency() {
        if ($this->basket == null ) {
            return "NO Basket found";
        }

        if ($this->itemToDelete === null) {
            return "Item to delete not found";
        }

        if ($this->itemToMerge === null) {
            return "Item to merge not found";
        }

        if ($this->itemToDelete->getBasketId() != $this->basket->getId()) {
            return "The item to delete does not belong to the basket passed as parameter";
        }

        if ($this->itemToMerge->getBasketId() != $this->basket->getId()) {
            return "The item to merge does not belong to the basket passed as parameter";
        }


        return true;
    }

}