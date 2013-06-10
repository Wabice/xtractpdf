<?php

namespace XtractPDF\Command;

use XtractPDF\Core\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\TableHelper;
use XtractPDF\Model\Document as DocumentModel;
use Silex\Application;
use RuntimeException;

/**
 * Extract XML from PDF via CLI
 */
class Extract extends BaseCommand
{
    /** 
     * @var XtractPDF\Extractor\ExtractorInterface
     */
    private $extrator;

    /**
     * @var XtractPDF\Library\DocumentMgr
     */
    private $docMgr;

    // --------------------------------------------------------------

    protected function configure()
    {
        $this->setName('extract')->setDescription('Clear all documents in the system');
        $this->addOption('persist', 'p', InputOption::VALUE_NONE, 'Persist this model to the database');
        $this->addArgument('file',  InputArgument::REQUIRED, 'Path to the PDF to extract');
    }

    // --------------------------------------------------------------

    public function init(Application $app)
    {
        $this->extractor  = $app['extractor'];
        $this->docMgr     = $app['doc_mgr'];        
    }

    // --------------------------------------------------------------

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //File location
        $file = $input->getArgument('file');

        //Persist?
        $persist = (boolean) $input->getOption('persist');

        //File readable?
        if ( ! is_readable($file)) {
            throw new RuntimeException("Can not read file (check path and permissions): " . $file);
        }

        //Get File MD5
        $fileMd5  = md5_file($file);
        $filePath = realpath($file);

        //Run the extractor
        $output->writeln(sprintf("Extracting %s (this may take a few moments)", basename($file)));
        $extractResult = $this->extractor->extract(realpath($file));

        //Run the mapper
        $model = $this->extractor->map($extractResult, new DocumentModel($fileMd5));

        //If persist, do it
        if ($persist) {
            $result = $this->docMgr->saveNewDocument($model, $filePath);

            $output->writeln($result 
                ? "Persisted new document to storage ID (" . $model->uniqId . ")" 
                : "Skipped persisting document.  It already exists"
            );
        }

        //Output the result as tables
        
        //Basic information
        $table = new TableHelper();
        $table->setHeaders(array('Item', 'Value'));
        foreach($model->toArray() as $k => $val) {
            if (is_scalar($val)) {
                $table->addRow(array($k, $val));
            }
        }
        foreach($model->biblioMeta as $k => $val) {
            $table->addRow(array($k, $val));
        }
        $output->writeln('Basic Information');
        $table->render($output);

        //Sections
        $table = new TableHelper();
        $table->setHeaders(array('Section', 'Paragraphs'));
        foreach($model->sections as $sec) {            
            $table->addRow(array($sec->title, count($sec->paragraphs)));
        }
        $output->writeln('Sections');
        $table->render($output);

        //Citations
        $table = new TableHelper();
        $table->setHeaders(array('#', 'Citation'));
        foreach($model->citations as $n => $cite) {            
            $table->addRow(array($n, substr($cite->content, 0, 60) . '...' ));
        }
        $output->writeln('Citations');
        $table->render($output);
    }
}

/* EOF: Extract.php */