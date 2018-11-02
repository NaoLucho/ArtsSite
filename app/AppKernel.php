<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            //Sonata bundles needed
            new Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),

            new Sonata\EasyExtendsBundle\SonataEasyExtendsBundle(),

            new Sonata\AdminBundle\SonataAdminBundle(),

            //Doctrine extensions
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            //Doctrine updates 
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),

            //User bundle (FOS & Sonata)
            new FOS\UserBundle\FOSUserBundle(),
            new Sonata\UserBundle\SonataUserBundle(),
            new Application\Sonata\UserBundle\ApplicationSonataUserBundle(),

            //SonataMediaBundle
            new Sonata\MediaBundle\SonataMediaBundle(),
            // You need to add this dependency to make media functional
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Application\Sonata\MediaBundle\ApplicationSonataMediaBundle(),

            //SEO bundle => not work correctly
            //new Leogout\Bundle\SeoBundle\LeogoutSeoBundle(),

            //SonataMediaBundle pour CKeditor
            new CoopTilleuls\Bundle\CKEditorSonataMediaBundle\CoopTilleulsCKEditorSonataMediaBundle(),
            //CKEditor ivoryCKEditorBundle
            // sudo apt-get install php7.1-zip
            // php bin/console ckeditor:install
            // php bin/console assets:install web
            // chmod -R 0777 web/uploads
            new Ivory\CKEditorBundle\IvoryCKEditorBundle(),

            //Bundles fait maison
            new SiteBundle\SiteBundle(),
            new Builder\PageBundle\BuilderPageBundle(),
            new Builder\ListBundle\BuilderListBundle(),
            new Builder\FormBundle\BuilderFormBundle(),
            new AdminBundle\AdminBundle(),

            //Gestion des fichiers CSS et JS (Assetic)
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),

            //Gestion des images
            new Vich\UploaderBundle\VichUploaderBundle(),
            
            //Captcha
            new Gregwar\CaptchaBundle\GregwarCaptchaBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            //Bundle classique pour les fixtures
            $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();

            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
                $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
            }

            //Console online web
            $bundles[] = new CoreSphere\ConsoleBundle\CoreSphereConsoleBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
