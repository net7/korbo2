<?php
namespace Net7\KorboApiBundle\Command;

use Net7\KorboApiBundle\Entity\Basket;
use Net7\KorboApiBundle\Entity\Item;
use Net7\KorboApiBundle\Entity\ItemTranslation;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Solarium\QueryType\Update\Query\Document\Document;
use Solarium\Client;


/**
 * Class IndexSolr
 *
 * @package Net7\KorboApiBundle\Command
 *
 */
class IndexSolrCommand extends ContainerAwareCommand
{

    private $connection;



    protected function configure()
    {
        parent::configure();
        $this
            ->setName('korbo:index:solr')
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

        $this->connection = $this->getContainer()->get('database_connection');
        $em = $this->getContainer()->get('doctrine')->getManager();

        $items = $em->getRepository('Net7\KorboApiBundle\Entity\Item')->findAll();


        $config = array(
            'default' => array(
                'host' => 'thepund.it',
                'port' => 8080,
                'path' => '/korbo2-solr/',
            )
        );


/*        $config = array(
            'default' => array(
                'host' => 'localhost',
                'port' => 8983,
                'path' => '/solr/',
            )
        );
*/
        $client = new Client(array('endpoint' => $config));
        $update = $client->createUpdate();

        $count = 0;

        $documents = array();
        foreach ($items as $item) {

            if (++$count >= 100){
                $count = 0;
                $update->addDocuments($documents);
                $update->addCommit();
                $client->update($update);
                $update = $client->createUpdate();
                $documents = array();
            }

            $doc = $update->createDocument();
            $doc->id = $item->getId();
            $doc->basket_id_s = $item->getBasketId();
            $doc->resource_s = $item->getResource();

            $types =  $item->getType();

            $doc->type_ss = json_decode($types, true);
            $doc->depiction_s = $item->getDepiction();
            $doc->abstract_txt = $item->getAbstract();
            $doc->label_ss = $item->getLabelTranslations();

            $documents[] =$doc;
        }

        $update->addDocuments($documents);
        $update->addCommit();

        $result = $client->update($update);

        echo "\r\n\r\nUpdate query executed \r\n";


    }

}