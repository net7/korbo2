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
class SyncWithASCommand extends ContainerAwareCommand
{

    private $connection;

    /**
     * Setup function
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('korbo:sync-with-as')
            ->addArgument('basket-id',              InputArgument::REQUIRED, 'Basket ID')
            ->addArgument('as-sesame-api-base-url', InputArgument::REQUIRED, 'AS Api needed to retrieve the list of annotations to modify')
            ->addArgument('days-in-the-past', InputArgument::OPTIONAL, 'Number of days in the past starting from the script execution time ...')
        ;

        // se no numero di ore tutti
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

        $output->writeln('updating annotations...');

        if ($this->days === false) {
            $updatedAfter = '2010-01-01 12:00:00';
        } else {
            $updatedAfter = date('Y-m-d', strtotime(" -{$this->days} day"));
        }


        // 1. retrieving all the items updated after a certain time
        $items = $this->em
                 ->createQuery(
                    "SELECT i
                     FROM Net7KorboApiBundle:Item i
                     WHERE i.updatedAt > '{$updatedAfter}'")->getResult();

        //$items = array($this->em->find("Net7KorboApiBundle:Item", 720));
        if (count($items) == 0) {
            $output->writeln("\nNo annotations to update! \n");
            return;
        }


        foreach ($items as $item) {
            $item->setTranslatableLocale("en");
            $item->setBaseItemUri($this->getContainer()->getParameter('korbo_base_purl_uri'));
            // call AS api -- returns the list of annotations to update
            $annotations = $this->getAnnotations($item);
            if (count($annotations) > 0) {
                $output->writeln("\nThe the uri {$item->getUri()} is present in the following annotations: \n");
            }
            else {
                $output->writeln("\nNo annotations to update related to item {$item->getId()}! \n");
            }

            foreach ($annotations as $annotation) {
                $output->writeln($annotation['annotationId'] . ' ' .$annotation['s'] . '  --  ' . $annotation['p'] . '  --  ' . $item->getLabelTranslated() . "\n");
            }

            // updating the annotations
            //$output->writeln("Updating the annotations... \n");
            $updateAnnotationResponse = $this->updateAnnotations($annotations, $item, $output);
            if ($updateAnnotationResponse === false) {
                throw new Exception('Something went wrong...AS API not reachable or got error');
            }

            $output->writeln("\n\n");
            sleep(2);
        }



        $output->writeln("THIS IS THE END!");

    }


    private function updateAnnotations($annotations, $item, $output) {
        $itemsTypesForQuery = array();
        foreach ($item->getTypesArray() as $type) {
            $itemsTypesForQuery[] = '<' . $item->getUri() . '> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <' .  $type . '>';
        }
        $itemsTypesForQuery = implode(' . ', $itemsTypesForQuery);

        foreach ($annotations as $annotation) {

        $queryInsert = 'WITH <http://purl.org/pundit/as/graph/itemsGraph-' . $annotation["annotationId"] . '> ' .
        //$queryInsert = 'WITH <http://purl.org/pundit/demo-cloud-server/graph/itemsGraph-' . $annotation["annotationId"] . '> ' .
            'DELETE { <' . $item->getUri() . '> ?p ?o } ' .
            'INSERT { <' . $item->getUri() . '>  <http://www.w3.org/2000/01/rdf-schema#label> "' . $item->getLabelTranslated() .  '" . ' .
            '<' . $item->getUri() .  '>  <http://www.w3.org/2004/02/skos/core#label> "' . $item->getLabelTranslated() . '" . ' .
            '<' . $item->getUri() . '>  <http://purl.org/dc/elements/1.1/description> "' .  preg_replace( "/\r|\n/", "", $item->getAbstractTranslated() ) .  '" . ' .
            '<' . $item->getUri() . '>  <http://xmlns.com/foaf/0.1/depiction> "'  .  $item->getDepiction() . '" . ' .
            $itemsTypesForQuery .
            '} ' .
            'WHERE{ <' . $item->getUri() . '> ?p ?o }' ;

            $output->writeln("\nQUERY INSERT:" . $queryInsert . "\n\n\n");

            $response = $this->doPostApiRequest($this->asSesameBaseApiUrl . "/openrdf-workbench/repositories/pundit/update",  http_build_query(array('update' => $queryInsert, 'queryLn' => "SPARQL")));

            if ($response === false) {
                return false;
            }
        }

        return true;
    }


    private function getAnnotations($item) {
        //TODO: sostituire con uri da eliminare....
        $uri = $item->getUri();

        $annotations = array();

        $query = <<<EOT
select ?s ?p ?o ?c
where {
GRAPH ?c {
?s ?p <{$uri}>
}
}

EOT;

        $annotationList = json_decode($this->doApiRequest($this->asSesameBaseApiUrl . "/openrdf-sesame/repositories/pundit?query=" . urlencode($query) . '&queryLn=SPARQL'), true);

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
                //'page-context' => $annotationMetadata['metadata']["http://purl.org/pundit/demo-cloud-server/annotation/" . $annotationId]['http://purl.org/pundit/ont/ao#hasPageContext'][0]['value']);
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

    }

    private function setUpEnvironment(InputInterface $input) {

        $basketId = $input->getArgument('basket-id');
        $this->asSesameBaseApiUrl = $input->getArgument('as-sesame-api-base-url');

        $this->em = $this->getContainer()->get('doctrine')->getEntityManager();
        $this->basket = $this->em->find("Net7KorboApiBundle:Basket", $basketId);

        $this->days = (trim($input->getArgument('days-in-the-past')) != '') ?  $input->getArgument('days-in-the-past') : false;

        if ($this->basket == null) {
            throw new Exception("Basket" . $basketId. ' does not exist');
        }
    }
}