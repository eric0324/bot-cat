<?php

class BotCatRoleService
{
    private $options;
    private $enable_service;

    public function __construct()
    {
        $this->options = [];
        $this->enable_service = [];

        foreach (SERVICES as $service) {
            $option = get_option(BOT_CAT_OPTION_PREFIX . $service);

            if (isset($option['is_enable'])) {
                $this->options[$service] = $option;
                $this->enable_service[] = $service;
            }
        }
    }

    /**
     * @return array
     */
    public function get_enable_services(): array
    {
        return $this->enable_service;
    }

    /**
     * @param $action_name
     *
     * @return array
     */
    public function get_service_uuids($action_name): array
    {
        $service_uuids = [];
        foreach (SERVICES as $service) {
            $uuids = [];
            if (
                isset($this->options[$service][$action_name]) &&
                in_array($service, $this->enable_service, false)
            ) {
                foreach ($this->options[$service][$action_name] as $key => $role) {
                    $user_list = get_users(['role' => $key]);
                    foreach ($user_list as $user) {
                        $meta = get_user_meta($user->ID, BOT_CAT_OPTION_PREFIX . $service . '_uuid', true);
                        if ($meta) {
                            $uuids[] = $meta;
                        }
                    }
                }
                $service_uuids[$service] = array_unique($uuids);
            }
        }

        return $service_uuids;
    }

    /**
     * @param $action_name
     *
     * @return array
     */
    public function get_can_manage_woocommerce_service_uuids($action_name): array
    {
        $uuids = [];
        foreach (SERVICES as $service) {
            $uuids = [];
            if (
                isset($this->options[$service][$action_name]) &&
                in_array($service, $this->enable_service, false)
            ) {
                foreach ($this->options[$service][$action_name] as $key => $role) {
                    if ($role !== 'customer') {
                        $user_list = get_users(['role' => $key]);
                        foreach ($user_list as $user) {
                            $meta = get_user_meta($user->ID, BOT_CAT_OPTION_PREFIX . $service . '_uuid', true);
                            if ($meta) {
                                $uuids[] = $meta;
                            }
                        }
                    }
                }
            }

            $uuids[$service] = array_unique($uuids);
        }

        return $uuids;

    }

    /**
     * @param $action_name
     * @param $order
     *
     * @return array
     */
    public function get_customer_service_uuids($action_name, $order): array
    {
        $user = $order->get_user();

        $uuids = [];
        foreach (SERVICES as $service) {
            $uuids = [];
            if (
                isset($this->options[$service][$action_name]) &&
                $this->options[$service][$action_name]['customer'] &&
                in_array($service, $this->enable_service, false)
            ) {
                $meta = get_user_meta($user->ID, BOT_CAT_OPTION_PREFIX . $service . '_uuid', true);
                if ($meta) {
                    $uuids[] = $meta;
                }
            }

            $uuids[$service] = array_unique($uuids);
        }

        return $uuids;
    }
}