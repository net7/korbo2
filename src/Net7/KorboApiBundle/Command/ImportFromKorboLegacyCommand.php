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

/**
* Class FixturesLoadDirectCommand
*
* @package Net7\OpenpalApiBundle\Command
* 
* TODO: Add documentation
* TODO: Add test(s)
* TODO: Errors and warning management with class properties and not locally
* TODO: NULL management
*/
class ImportFromKorboLegacyCommand extends ContainerAwareCommand
{

	private $connection;
	
	private $path;
	private $csv;

    // TODO to replace
    private $langsArray;

	private $items;

    private $basketName;
	/**
     *
     * The naming convention is the following
     *
     * {base-file-name}.csv
     * {base-file-name}_en.csv
     * {base-file-name}_it.csv
     * {base-file-name}_de.csv
     *
	* Setup function
	*/
	protected function configure()
	{
		parent::configure();
		$this
			->setName('korbo:import:from:korbo:legacy')
            ->addArgument('file-name', InputArgument::REQUIRED, 'path/to/main/import/file/name')
            ->addArgument('basket-name', InputArgument::OPTIONAL, 'basket name', 'XXX')

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

		// Input files
		$this->path = $input ->getArgument('file-name');
		$this->basketName = $input ->getArgument('basket-name');
		$this->langsArray = array("it", "en", "de");

        $this->read();

		$output->write("\n");
		$output->write("-- Everithing fine: import started!\n");
		$output->write("\n");
	   
	   
	    $this->persist($output);
	   
		$output->write("\n\n  Import completed \n\n\n");
	}
	
	/**
	* Read export from korbo
	*/
	private function read() {

        $this->items = array();

		// READ csv and fill $this->items
		$file = fopen($this->path,'r');
		$csv = array();

		while (($result = fgetcsv($file,0, ",", '"')) !== false)
		{
            // costruisco hash
			$csv[$result[0]] = $result;
        }
        fclose($file);
        // READ it/en/de

        foreach ($this->langsArray as $lang) {
            $filename = str_replace('.csv', "_$lang.csv", $this->path);
            if (file_exists($filename)) {
                $file = fopen($filename,'r');

                /*while (($line = fgets($file)) !== false) {
                    $csv[$result[0]]['translations'][$lang] = explode(",", $line);
                    print_r(explode(",", $line));
                    // process the line read.
                }*/
                while (($result = fgetcsv($file,0,',','"')) !== false)
                {
                    $csv[$result[0]]['translations'][$lang] =  $result;
                }
                fclose($file);
            }
        }

        $this->items = $csv;

		return;		
	}

	private function persist(OutputInterface $output) {
		
		$this->connection = $this->getContainer()->get('database_connection');
		$em = $this->getContainer()->get('doctrine')->getManager();

        $basket = new Basket();
        $basket->setLabel($this->basketName);

        $em->persist($basket);
        $em->flush();

        // output file per import su dl
        $fp = fopen('/tmp/mapping_korbo1_korbo2.csv', 'w');


        foreach ($this->items as $id => $meta ) {

            $output->write("\n --> Importing item $id\n");
            $resource = ($meta[2] != null && $meta[2] != 'null') ? $meta[2] : '';
            $depiction = ($meta[1] != null && $meta[1] != 'null') ? $meta[1] : '';
            $item = new Item();

            $item->setBasket($basket);
            $item->setDepiction($depiction);
            $item->setType(json_encode(explode('|||', $meta[3])));
            $item->setResource($resource);
            foreach ($meta['translations'] as $lang => $translation ) {
                if (isset($translation[1])) {
                    $label = (isset($translation[1][1]) && strpos($translation[1][1], '"') === 0) ? substr($translation[1], 1, -1) : $translation[1];
                    $item->addTranslation(new ItemTranslation($lang, 'label', $label));
                }
                $t = array();
                /*for ($i = 2; $i < count($translation) - 1; $i++) {
                    $label = (strpos($translation[$i][1], '"') === 0) ? substr($translation[$i], 1, -1) : $translation[$i];
                    $output->write($label);
                    $t[] = $label;
                }
                $item->addTranslation(new ItemTranslation($lang, 'abstract',implode(',', $t)));
            */
                if (isset($translation[2])) {
                    $label = (isset($translation[2][1]) && strpos($translation[2][1], '"') === 0) ? substr($translation[2], 1, -1) : $translation[2];
                    $item->addTranslation(new ItemTranslation($lang, 'abstract', $label));
                }
            }


            $em->persist($item);
            $em->flush();

            fputcsv($fp, array($item->getId(), $id));

        }
        fclose($fp);



    }

	

}