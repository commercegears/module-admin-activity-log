/**
 * CommerceGears Admin Activity Log
 *
 * Filters component without the collapsible panel: the fields live in the grid's filter row (see listing.js),
 * the toolbar shows Search and Reset Filter buttons and the chips of the applied filters.
 */
define([
    'underscore',
    'Magento_Ui/js/grid/filters/filters'
], function (_, Filters) {
    'use strict';

    return Filters.extend({
        defaults: {
            template: 'CommerceGears_ActivityLog/grid/filters',
            stickyTmpl: 'CommerceGears_ActivityLog/grid/sticky-filters'
        },

        /**
         * Select filters list each label once: several stored codes can share a label (Action "Save" covers created
         * and changed records; the server matches all codes of the label).
         *
         * @param {Object} column
         * @returns {Object}
         */
        buildFilter: function (column) {
            var filter = this._super(column),
                options = filter.options,
                seen = {};

            if (_.isString(options)) {
                try {
                    options = JSON.parse(options);
                } catch (e) {
                    return filter;
                }
            }

            if (_.isArray(options)) {
                filter.options = _.filter(options, function (option) {
                    var label = String(option.label);

                    if (_.has(seen, label)) {
                        return false;
                    }
                    seen[label] = true;

                    return true;
                });
            }

            return filter;
        },

        /**
         * Clears every filter field, applied or only typed, and reloads the grid.
         *
         * @returns {Filters} Chainable.
         */
        reset: function () {
            this.elems.each(function (filter) {
                filter.clear();
            });

            return this.apply();
        },

        /**
         * Sticky header button: back to the top of the grid, cursor in the first filter field.
         */
        scrollToFilters: function () {
            var field = document.querySelector('[data-role="filter-row"] input, [data-role="filter-row"] select');

            window.scrollTo(0, 0);

            if (field) {
                field.focus();
            }
        }
    });
});
