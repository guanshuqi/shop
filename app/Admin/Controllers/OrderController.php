<?php

namespace App\Admin\Controllers;

use App\Model\OrderModel;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class OrderController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('Index')
            ->description('description')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new OrderModel);

        $grid->order_id('Order id');
        $grid->order_sn('Order sn');
        $grid->uid('Uid');
        $grid->add_time('Add time')->display(function($time){
            return date('Y-m-d H:i:s',$time);
        });;
        $grid->add_amount('Add amount');
        $grid->pay_amount('Pay amount');
        $grid->pay_time('Pay time')->display(function($time){
            return date('Y-m-d H:i:s',$time);
        });;
        $grid->is_delete('Is delete')->display(function($delete){
            if($delete==0){
                return '未删除';
            }else{
                return '已删除';
            }
        });
        $grid->is_pay('Is pay')->display(function($pay){
            if($pay==1){
                return '未支付';
            }else{
                return '已支付';
            }
        });;
        $grid->plat_oid('Plat oid');
        $grid->plat('Plat');

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(OrderModel::findOrFail($id));

        $show->order_id('Order id');
        $show->order_sn('Order sn');
        $show->uid('Uid');
        $show->add_time('Add time');
        $show->add_amount('Add amount');
        $show->pay_amount('Pay amount');
        $show->pay_time('Pay time');
        $show->is_delete('Is delete');
        $show->is_pay('Is pay');
        $show->plat_oid('Plat oid');
        $show->plat('Plat');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new OrderModel);

        $form->number('order_id', 'Order id');
        $form->text('order_sn', 'Order sn');
        $form->number('uid', 'Uid');
        $form->number('add_time', 'Add time');
        $form->number('add_amount', 'Add amount');
        $form->number('pay_amount', 'Pay amount');
        $form->number('pay_time', 'Pay time');
        $form->switch('is_delete', 'Is delete');
        $form->switch('is_pay', 'Is pay')->default(1);
        $form->text('plat_oid', 'Plat oid');
        $form->number('plat', 'Plat')->default(1);

        return $form;
    }
}
