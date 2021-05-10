<?php

namespace ESL\Base;

use EasySwoole\Component\Di;
use EasySwoole\Http\AbstractInterface\Controller;
use App\Constant\AppConst;
use App\Constant\ResultConst;
use ESL\Output\Json;
use ESL\Exception\WaringException;
use ESL\Proxy\ValidateProxy;
use ESL\Proxy\ServiceProxy;
use App\Constant\PageConst;

class BaseController extends Controller
{
    protected $service;

    function get()
    {
        /** @var $result Result */
        $result = Di::getInstance()->get(AppConst::DI_RESULT);

        /** 获取参数 */
        $params = $this->getParams();

        /** 如果传递了id 就查询 */
        $data = null;
        $id = $params['id'] ?? null;
        if ($id) {
            $data = $this->getService()->get(['id' => $id]);
        }

        /** 返回结果 */
        $result->set(ResultConst::RESULT_DATA_KEY, $data);
        Json::success();
    }

    function index()
    {
        /** @var $result Result */
        $result = Di::getInstance()->get(AppConst::DI_RESULT);

        /** 获取分页参数  */
        $page = $this->getPage();

        /** 获取所有参数 */
        $params = $this->getParams();

        /** 去掉不属于该表之外的字段 */
        $params = $this->getService()->adjustWhere($params);

        /** 查询列表 */
        $dataList = $this->getService()->getAll($params, $page, [], [], []);

        $result->set(ResultConst::RESULT_TOTAL_KEY, $dataList[ResultConst::RESULT_TOTAL_KEY]);
        $result->set(ResultConst::RESULT_LIST_KEY, $dataList[ResultConst::RESULT_LIST_KEY]);

        Json::success();
    }

    function delete()
    {
        /** @var $result Result */
        $result = Di::getInstance()->get(AppConst::DI_RESULT);

        /** 获取参数 参数调整 */
        $params = $this->getParams();
        $this->getService()->getLogicDelete();

        /** 先查一下 不存在就报错 */
        $id = $params['id'] ?? null;
        $data = $this->getService()->get(['id' => $id]);
        if (!isset($data)) {
            throw new WaringException(1009, "数据不存在或已删除");
        }

        /** 执行删除 */
        $total = $this->getService()->delete([$id]);
        $result->set(ResultConst::RESULT_TOTAL_KEY, $total);

        Json::success();
    }

    function update()
    {
        /** @var $result Result */
        $result = Di::getInstance()->get(AppConst::DI_RESULT);

        /** 获取参数 参数调整 */
        $params = $this->getParams();
        /** 如果传递了Id 就查询 */
        $id = $params['id'] ?? null;
        $data = $this->getService()->get(['id' => $id]);

        if (!isset($data)) {
            throw new WaringException(1009, "数据不存在或已删除");
        }

        /** 敏感参数自行过滤 */
        $total = $this->getService()->update($params, [$id]);

        $data = $this->getService()->get(['id' => $id]);
        $result->set(ResultConst::RESULT_TOTAL_KEY, $total);
        $result->set(ResultConst::RESULT_DATA_KEY, $data);

        Json::success();
    }

    function save()
    {
        /** @var $result Result */
        $result = Di::getInstance()->get(AppConst::DI_RESULT);

        /** 获取所有参数 */
        $params = $this->getParams();

        /** 保存数据 */
        $id = $this->getService()->save($params);

        /** 查询插入的数据 */
        $data = $this->getService()->get(['id' => $id]);

        $result->set(ResultConst::RESULT_DATA_KEY, $data);

        Json::success();
    }

    protected function onRequest(?string $action): ?bool
    {
        try {
            /** 验证器代理 */
            $validateProxy = new ValidateProxy(get_called_class());
            $validateProxy->validate($action, $this->getParams());

            /** service 代理 */
            $serviceProxy = new ServiceProxy(get_called_class());
            $service = $serviceProxy->getService();
            if ($service) {
                $this->setService($service);
            }

            return true;
        } catch (\Throwable $throwable) {
            Json::fail($throwable, $throwable->getCode(), $throwable->getMessage());
            return false;
        }

        return true;
    }

    /**
     * 获取所有请求参数
     * @return array
     */
    public function getParams(): array
    {
        $params = $this->request()->getRequestParam();

        $raw = $this->request()->getBody()->__toString();
        $rawParams = json_decode($raw, true);

        if ($rawParams) {
            $params = $rawParams + $params;
        }

        return $params;
    }

    protected function onException(\Throwable $throwable): void
    {
        throw $throwable;
    }

    /**
     * 控制器找不到时
     * @param string|null $action
     */
    protected function actionNotFound(?string $action)
    {
        $this->response()->withStatus(404);
        $this->response()->end();
    }

    /**
     * 获取分页参数
     */
    public function getPage(): array
    {
        $params = $this->getParams();

        $pageNo = $params[PageConst::PAGE_NO_KEY] ?? 0;
        $pageNum = $params[PageConst::PAGE_NUM_KEY] ?? PageConst::PAGE_DEFAULT_NUM;

        // 前端不传递分页 给个默认
        if (0 === $pageNo) {
            return [0, intval($pageNum)];
        }

        $pageNo = $pageNo > 0 ? $pageNo : 1;
        $offset = ($pageNo - 1) * $pageNum;

        return [intval($offset), intval($pageNum)];
    }

    /**
     * @return mixed
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param mixed $service
     */
    public function setService($service): void
    {
        $this->service = $service;
    }
}
