<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\BusinessWebsiteContent;
use App\Models\Website;
use App\Services\AuthService;
use App\Services\BusinessWebsiteService;
use App\Services\ProjectStorageService;

class BusinessWebsiteController extends Controller
{
    public function edit(): void
    {
        [$userId,$website]=$this->owned();
        $service=new BusinessWebsiteService();
        $content=(new BusinessWebsiteContent())->findForOwner((int)$website['id'],$userId) ?? $service->defaults((string)$website['website_name']);
        $this->view('websites/business-edit',['title'=>'Customize Business Website','website'=>$website,'content'=>$content,'active'=>'websites'],'layouts/dashboard');
    }
    public function save(): void
    {
        [$userId,$website]=$this->owned();$id=(int)$website['id'];
        if(!Csrf::validate($this->input('_csrf'))){Session::flash('error','Your session expired. Please try again.');$this->redirectTo('/dashboard/websites/business?id='.$id);}
        try {
            $service=new BusinessWebsiteService();
            $content=$service->validate($this->request(),(string)$website['website_name']);
            (new BusinessWebsiteContent())->saveForOwner($id,$userId,$content);\n            (new Website())->updateProject($id,$userId,$content['business_name']);
            $service->writeDraft($userId,(string)$website['slug'],$content);
            $storage=new ProjectStorageService();
            (new Website())->updateStorageUsed($id,$storage->directorySize($storage->projectPath($userId,(string)$website['slug'])));
            Session::flash('success','Business website content saved. Preview your changes before publishing.');
        } catch (\InvalidArgumentException $exception) {
            Session::flash('error',$exception->getMessage());
        } catch (\Throwable $exception) {
            Session::flash('error','The business website could not be updated. Confirm that the content migration has been applied.');
        }
        $this->redirectTo('/dashboard/websites/business?id='.$id);
    }
    private function owned(): array
    {
        if(!AuthService::check()) $this->redirectTo('/login');
        $userId=(int)AuthService::id();
        $website=(new Website())->findForUser((int)$this->input('id',0),$userId);
        if(!$website || strtolower((string)($website['template_key']??''))!=='business'){
            Session::flash('error','Business website not found.');
            $this->redirectTo('/dashboard/websites');
        }
        return [$userId,$website];
    }
}
