<?php
namespace faysal0x1\PaymentGateway\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakePaymentGatewayCrudCommand extends Command
{
    protected $signature = 'payment:make-crud {--force : Overwrite existing files}';
    protected $description = 'Create CRUD files for payment gateway management';

    public function handle()
    {
        $this->createController();
        $this->createViews();
        $this->addRoutes();
        $this->info('Payment Gateway CRUD files generated successfully!');
    }

    protected function createController()
    {
        $controllerPath = app_path('Http/Controllers/Admin/PaymentGatewayController.php');
        
        if (!$this->option('force') && File::exists($controllerPath)) {
            $this->warn('Controller already exists. Use --force to overwrite.');
            return;
        }

        $stub = File::get(__DIR__.'/../../../stubs/controller.stub');
        File::ensureDirectoryExists(dirname($controllerPath));
        File::put($controllerPath, $stub);
    }

    protected function createViews()
    {
        $viewPath = resource_path('views/admin/payment-gateways');
        File::ensureDirectoryExists($viewPath);

        $views = ['index', 'create', 'edit', '_form', 'show'];
        
        foreach ($views as $view) {
            $filePath = "$viewPath/$view.blade.php";
            if (!$this->option('force') && File::exists($filePath)) {
                $this->warn("View $view already exists. Use --force to overwrite.");
                continue;
            }
            File::put($filePath, File::get(__DIR__."/../../../stubs/views/$view.blade.stub"));
        }
    }

    protected function addRoutes()
    {
        $routeFile = base_path('routes/admin.php');
        $routeStub = File::get(__DIR__.'/../../../stubs/routes/admin.php.stub');

        if (File::exists($routeFile)) {
            $content = File::get($routeFile);
            if (strpos($content, 'payment-gateways') === false) {
                File::append($routeFile, "\n\n" . $routeStub);
            }
        } else {
            File::put($routeFile, "<?php\n\n" . $routeStub);
        }
    }
}