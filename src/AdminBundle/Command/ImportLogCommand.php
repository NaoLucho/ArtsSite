<?php
namespace AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;

use AdminBundle\Entity\DBLog;

//http://www.jesuisundev.fr/massive-import-via-symfony2-command-depuis-fichier-csv/

class ImportLogCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        // Name and description for app/console command
        $this
        ->setName('importlog:csv')
        ->setDescription('Import users from CSV file')
        ->addArgument('fileName', InputArgument::REQUIRED, 'fileName')
        ->addArgument('source', InputArgument::REQUIRED, 'source');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Showing when the script is launched
        $now = new \DateTime();
        $output->writeln('<comment>Start : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');
        
        // Importing CSV on DB via Doctrine ORM
        $this->import($input, $output);
        
        // Showing when the script is over
        $now = new \DateTime();
        $output->writeln('<comment>End : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');
    }
    
    protected function import(InputInterface $input, OutputInterface $output)
    {
        // Getting php array of data from CSV
        $data = $this->get($input, $output);
        
        $force = true;
        
        // Getting doctrine manager
        $em = $this->getContainer()->get('doctrine')->getManager();
        // Turning off doctrine default logs queries for saving memory
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        
        // Define the size of record, the frequency for persisting the data and the current index of records
        $size = count($data);
        $batchSize = 1;
        $i = 1;
        $nbImport = 0;
        // Starting progress
        $progress = new ProgressBar($output, $size);
        $progress->start();
        
        $source = $input->getArgument('source');

        // Processing on each row of data
        foreach($data as $row) {
            
            $now = new \DateTime();
            //dump($row['createdAt']);
            $createdAt = \DateTime::createFromFormat("d/m/Y H:i:s",$row['createdAt']);
            //dump($createdAt);
            $dblog = $em->getRepository('AdminBundle:DBLog')
                ->checkIfDBLogExists($createdAt, $row['userName']);
            // // If the DBLog doest not exist we create one to import it
            //dump($dblog);
            if(!is_object($dblog) || $force)
            {
                $dblog = new DBLog();
                // Updating info
                $dblog->setEnv($source);
                $dblog->setCreatedAt($createdAt);
                $dblog->setUserName($row['userName']);
                $dblog->setAction($row['action']);
                $dblog->setEntityName($row['entityName']);
                $dblog->setEntityId($row['entityId']);
                $dblog->setPropertyName($row['propertyName']);
                $dblog->setOldValue($row['oldValue']);
                $dblog->setNewValue($row['newValue']);
                $dblog->setStatus("waiting");

                //dump($dblog);
                // Persisting the current dblog
                $em->persist($dblog);
                
                // Each 20 dblog persisted we flush everything
                if (($i % $batchSize) === 0) {

                    $em->flush();
                    // Detaches all objects from Doctrine for memory save
                    $em->clear();
                    
                    // Advancing for progress display on console
                    $progress->advance($batchSize);
                    
                    $now = new \DateTime();
                    $output->writeln($i.' of '.$size.' dblog imported ... | ' . $now->format('d-m-Y G:i:s'));
                    //dump($output);
                }
                $nbImport++;
                
            }
            else
            {
                $progress->advance($batchSize);
                $output->writeln(' DBLog '.$dblog->getId().' exists ... | ' . $now->format('d-m-Y G:i:s'));
                //dump($output);
            }
            $i++;
            $progress->display(); $output->write(' ');
        }
		$output->writeln($nbImport.' of '.$size.' dblog imported ... | ' . $now->format('d-m-Y G:i:s'));
		// Flushing and clear data on queue
        $em->flush();
        $em->clear();
		
		// Ending the progress bar process
        $progress->finish();
    }

    protected function get(InputInterface $input, OutputInterface $output) 
    {
        // Getting the CSV from filesystem
        $fileName = $input->getArgument('fileName');
        $fileName = 'web/uploads/import/'.$fileName;
        
        // Using service for converting CSV to PHP Array
        $converter = $this->getContainer()->get('import.csvtoarray');
        $data = $converter->convert($fileName, ',');
        
        return $data;
    }
    
}