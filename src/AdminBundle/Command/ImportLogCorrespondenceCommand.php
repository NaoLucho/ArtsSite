<?php
namespace AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;

use AdminBundle\Entity\DBLogCorrespondence;

//http://www.jesuisundev.fr/massive-import-via-symfony2-command-depuis-fichier-csv/

class ImportLogCorrespondenceCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        // Name and description for app/console command
        $this
        ->setName('importlogID_env_file_source:csv')
        ->setDescription('Import correspondenceID from CSV file\n params: env_exe file env_source\n exemple en prod= importlogID_env_file_source:csv \'prod\' export_dblogcorrespondence_2018_05_24.csv \'devl\' ')
        ->addArgument('env', InputArgument::REQUIRED, 'env')
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
        $envExe = $input->getArgument('env');
        $now = new \DateTime();
        // Processing on each row of data
        foreach($data as $row) {
            if($row['env'] != $envExe){
                
                //search DBlogCorrespondence where:
                // env == $row['env'] && entityName == $row['entityName']
                // && entityIdDistant == $row['entityIdDistant']
                $local_dblogCorrespondences = $em->getRepository('BuilderPageBundle:DBLogCorrespondence')
                    ->findBy([
                        'env' => $row['env'],
                        'entityName' => $row['entityName'],
                        'entityIdDistant' => $row['entityIdDistant']
                    ]);
    
                if($local_dblogCorrespondences != null && count($local_dblogCorrespondences) > 0){
                    //normalement count = 0 ou 1
                    foreach($local_dblogCorrespondences as $local_dblogCorrespondence){
                        $env = $source;
                        $entityIdDistant = $row['entityIdLocal'];
                        $entityIdLocal = $local_dblogCorrespondence->getEntityIdLocal();

                        //check if exists already
                        $exist_dblogCorrespondences = $em->getRepository('BuilderPageBundle:DBLogCorrespondence')
                        ->findBy([
                            'env' => $env,
                            'entityName' => $entityIdDistant,
                            'entityIdDistant' => $entityIdDistant,
                            'entityIdLocal' => $entityIdLocal
                        ]);

                        if($exist_dblogCorrespondences != null && count($exist_dblogCorrespondences)>0){
                            //dblogCorrespondence already exists
                            continue;
                        } else {
                            $dblogC = new DBLogCorrespondence();
                            // Updating info
                            $dblogC->setEnv($env);
                            $dblogC->setCreatedAt($now);
                            $dblogC->setEntityName($entityName);

                            $dblogC->setEntityIdDistant($entityIdDistant);
                            $dblogC->setEntityIdLocal($entityIdLocal);

    
                            // Persisting the current dblogC
                            $em->persist($dblogC);

                            // Each 20 dblog persisted we flush everything
                            // if (($i % $batchSize) === 0) {
            
                                // $em->flush();
                                // // Detaches all objects from Doctrine for memory save
                                // $em->clear();
                                
                                // // Advancing for progress display on console
                                // $progress->advance($batchSize);
                                
                                $now = new \DateTime();
                                // OK DBLOGCorrespondence créé
                                $output->writeln($i.' OK DBLOGCorrespondence créé :' . $now->format('d-m-Y G:i:s'));
                                //dump($output);
                            //}
                            $nbImport++;
                        }
                    }
                }

                
            }
            
            //avancement
            $output->writeln(' DBLog '.$dblog->getId().' exists ... | ' . $now->format('d-m-Y G:i:s'));
            $progress->advance($batchSize);
            
            //dump($output);
            
            $i++;
            $progress->display(); $output->write(' ');
        }

		$output->writeln($nbImport.' of '.$size.' dblogcorrespondence imported ... | ' . $now->format('d-m-Y G:i:s'));
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