<?php

namespace Honeygavi\Ui\Renderer\Haljson\Honeygavi\Ui\ValueObjects;

use Honeybee\Common\Util\ArrayToolkit;
use Honeygavi\Ui\Renderer\PaginationRenderer;

class HaljsonPaginationRenderer extends PaginationRenderer
{
    /**
     * @return array w/ pagination links
     */
    protected function doRender()
    {
        $pagination = $this->getPayload('subject');

        $current_page_url = $this->url_generator->generateUrl(null);
        $url_parameters = ArrayToolkit::getUrlQueryInRequestFormat($current_page_url);
        unset($url_parameters['offset']); // offset is not needed when page is used (see validator)

        $current_page_url = $this->url_generator->generateUrl(null, $url_parameters);

        $curie = $this->getOption('curie');
        $translation_domain = $this->getOption('translation_domain', 'application.ui');

        $links = [
            'self' => [
                'href' => $current_page_url
            ]
        ];

        if (!$pagination->isFirstPage()) {
            $links['first'] = [
                'href' => $this->url_generator->generateUrl(
                    null,
                    array_merge($url_parameters, [ 'offset' => 0 ])
                ),
                'title' => $this->translator->translate('pager.first_page.title', $translation_domain),
            ];
        }

        if ($pagination->hasPrevPage()) {
            $links['prev'] = [
                'href' => $this->url_generator->generateUrl(
                    null,
                    array_merge($url_parameters, [ 'offset' => $pagination->getPrevPageOffset() ])
                ),
                'title' => $this->translator->translate('pager.prev_page.title', $translation_domain),
            ];
        }

        if ($pagination->hasNextPage()) {
            $links['next'] = [
                'href' => $this->url_generator->generateUrl(
                    null,
                    array_merge($url_parameters, [ 'offset' => $pagination->getNextPageOffset() ])
                ),
                'title' => $this->translator->translate('pager.next_page.title', $translation_domain),
            ];
        }

        if (!$pagination->isLastPage()) {
            $links['last'] = [
                'href' => $this->url_generator->generateUrl(
                    null,
                    array_merge($url_parameters, [ 'offset' => $pagination->getLastPageOffset() ])
                ),
                'title' => $this->translator->translate('pager.last_page.title', $translation_domain),
            ];
        }

        if ($pagination->getNumberOfPages() > 1) {
            $link_name = empty($curie) ? 'jumpToPage' : $curie . ':jumpToPage';
            $links[$link_name] = [
                'href' => $this->url_generator->generateUrl(null),
                'templated' => true,
                'title' => $this->translator->translate('pager.jump_to_page.title', $translation_domain),
            ];
        }

        return $links;
    }
}
