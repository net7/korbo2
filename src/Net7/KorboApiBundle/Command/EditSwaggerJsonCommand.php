<?php
namespace Net7\KorboApiBundle\Command;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class EditSwaggerJsonCommand
 *
 * @package Net7\KorboApiBundle\Command
 */
class EditSwaggerJsonCommand extends ContainerAwareCommand
{

    private $connection;

    /**
     * Setup function
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('korbo:edit-swagger-json')
            //->addArgument('type', InputArgument::REQUIRED, 'Type')
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
//        $this->connection = $this->getContainer()->get('database_connection');
//        $type = $input->getArgument('type');
//        $country = $input->getOption('country');
//        $output->write('type: '.$type, true);

        $swaggerRoot = $this->getContainer()->get('kernel')->getRootDir() . '/../swagger/';

        $swaggerItemsConfigFile = $swaggerRoot . 'items.json';
        $swaggerJson = json_decode(file_get_contents($swaggerItemsConfigFile), true);

        $basePath = $this->getContainer()->getParameter('swagger_base_path');
        $apiPrefix = $this->getContainer()->getParameter('korbo_api_prefix');
        $swaggerJson['basePath'] = $basePath . '/' . $apiPrefix;

        file_put_contents($swaggerItemsConfigFile, json_encode($swaggerJson, JSON_PRETTY_PRINT));
        $output->write("Korbo2: Base Path changed correctly to {$basePath}", true);
        // Generates a config file for the UI, to have it ready and configured
        // for this instance korbo2
        $swaggerUIConfigFile = $swaggerRoot . 'swagger-korbo-conf.js';
        $swaggerUIConfig = <<<EOF
            var swaggerConf = {
                apidocsURL: "$basePath/api-docs"
            }
EOF;
        file_put_contents($swaggerUIConfigFile, $swaggerUIConfig);
        $output->write("Korbo2: wrote UI conf file {$swaggerUIConfigFile}", true);
        // Delete swagger generated index.php, useless
        $swaggerIndexPHPFile = $swaggerRoot . 'index.php';
        if (file_exists($swaggerIndexPHPFile)) {
            unlink($swaggerIndexPHPFile);
            $output->write("Korbo2: Deleted {$swaggerIndexPHPFile}", true);
        }
    }

}