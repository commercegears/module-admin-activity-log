/**
 * Softaware Admin Activity Log
 *
 * Grid with a filter row under the column headings (as in the Adobe Commerce action log report): the filters of
 * the toolbar's filters component are rendered in the row of the column they belong to and applied with Enter or
 * the Search button.
 */
define([
    'underscore',
    'Magento_Ui/js/grid/listing'
], function (_, Listing) {
    'use strict';

    return Listing.extend({
        defaults: {
            template: 'Softaware_ActivityLog/grid/listing',
            filtersProvider: '${ $.ns }.${ $.ns }.listing_top.listing_filters',
            filterElems: [],
            imports: {
                filterElems: '${ $.filtersProvider }:elems'
            },
            modules: {
                filtersComponent: '${ $.filtersProvider }'
            }
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super().observe(['filterElems']);

            return this;
        },

        /**
         * Filter component of a column, if the column has one.
         *
         * @param {Object} column
         * @returns {Object|undefined}
         */
        getColumnFilter: function (column) {
            return _.find(this.filterElems(), function (filter) {
                return filter.index === column.index;
            });
        },

        /**
         * Applies the values typed into the filter row.
         *
         * @returns {Boolean}
         */
        applyFilters: function () {
            var filters = this.filtersComponent();

            if (filters) {
                filters.apply();
            }

            return true;
        }
    });
});
