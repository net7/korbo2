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
class GetItemUsageCommand extends ContainerAwareCommand
{

    private $connection;

    /**
     * Setup function
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('korbo:get-item-usage')
            ->addArgument('basket-id',              InputArgument::REQUIRED, 'Basket ID')
            ->addArgument('item-id',           InputArgument::REQUIRED, 'Id of the Item id you want to search')
            ->addArgument('dl-get-api',             InputArgument::REQUIRED, 'DL Api needed to retrieve the list of entities to modify')
            ->addArgument('as-sesame-api-base-url', InputArgument::REQUIRED, 'AS Api needed to retrieve the list of annotations to modify')
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

        if ($this->itemToDelete == null) {
            $output->writeln("\nWARNING: The item ". $this->itemIdToDelete . " does not exist!!\n");
        }

        $output->writeln('Executing check...');

        if (($message = $this->checkItemsConsistency()) !== true) {
            //$this->logger->info($message);
            throw new Exception($message);
        }

        // call DL API -- returns the list of elements to merge
        $uri = ($this->itemToDelete == null) ? $this->getContainer()->getParameter('korbo_base_purl_uri') . $this->itemIdToDelete : $this->itemToDelete->getUri();

        $elementListUrl = $this->dlGetApiUrl . '?uri=' . $uri;
        $elementsToMergeRequest = $this->doApiRequest($elementListUrl, true);

        // something went wrong (not reachable, api error)
        if ($elementsToMergeRequest === false) {
            throw new Exception('Something went wrong...DL retrieving API not reachable');
        }

        $elementsToMerge = json_decode($elementsToMergeRequest, true);

        if (count($elementsToMerge ) > 0) $output->writeln("\nThe following elements containing the uri {$uri}: \n");
        else $output->writeln("\nThe element {$uri} is not present into the DL \n");
        // display the modification to make
        foreach ($elementsToMerge as $element) {
            foreach ($element['fields'] as $field => $values) {
                if ($values['uri'] == $uri) {
                    $output->writeln($element['id'] . ' ' . $element['slug']);
                    $output->writeln($field . ' : ' . $values['label']);
                }
            }
            $output->writeln("");
        }

        // call AS api -- returns the list of annotations to modify
        $annotations = $this->getAnnotations();
        //print_r($annotations);die;
        if (count($annotations) > 0) {
            $output->writeln("\nThe the uri {$uri} is present in the following annotations: \n");
        }
        else {
            $output->writeln("\nYou are lucky... no annotations found! \n");
        }

        foreach ($annotations as $annotation) {
            $output->writeln($annotation['annotationId'] . ' ' . $annotation['s'] . '  --  ' . $annotation['p'] . '  --  ' . $this->itemToDelete->getLabelTranslated() . '  --  ' . $annotation['page-context'] . "\n");
        }




    }

    private function getAnnotations() {
        //TODO: sostituire con uri da eliminare....
        //$uri = 'http://purl.org/net7/dev.korbo2/item/41';
        $uri = ($this->itemToDelete == null) ? $this->getContainer()->getParameter('korbo_base_purl_uri') . $this->itemIdToDelete : $this->itemToDelete->getUri();

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
//            if ($returnBooleanWhenErrorOccurs === true) {
//                if (json_decode($response, true) == '') return false;
//            }
            $result = $response;
        } else {
            if ($returnBooleanWhenErrorOccurs === true) return false;
            $result = $error;
        }

        curl_close($request);

        return $result;
    }



    private function setUpEnvironment(InputInterface $input) {

        $basketId = $input->getArgument('basket-id');
        $this->itemIdToDelete = $input->getArgument('item-id');
        $basketId = $input->getArgument('basket-id');
        $this->dlGetApiUrl = $input->getArgument('dl-get-api');
        $this->asSesameBaseApiUrl = $input->getArgument('as-sesame-api-base-url');


        $this->em = $this->getContainer()->get('doctrine')->getEntityManager();
        $this->logger = $this->getContainer()->get('logger');

        $this->itemToDelete = $this->em->find("Net7KorboApiBundle:Item", $this->itemIdToDelete);

//        if ($this->itemToDelete == null) {
//            throw new Exception("Item " . $itemIdToDelete . ' does not exist');
//            return;
//        }

        if ($this->itemToDelete != null) {
            $this->itemToDelete->setTranslatableLocale("en");
            $this->itemToDelete->setBaseItemUri($this->getContainer()->getParameter('korbo_base_purl_uri'));
        }

        $this->basket       = $this->em->find("Net7KorboApiBundle:Basket", $basketId);
    }


    private function checkItemsConsistency() {
        if ($this->basket == null ) {
            return "NO Basket found";
        }


        if ($this->itemToDelete && $this->itemToDelete->getBasketId() != $this->basket->getId()) {
            return "The item to delete does not belong to the basket passed as parameter";
        }



        return true;
    }

}