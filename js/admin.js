/**
 * Files_FromMail - Recover your email attachments from your cloud.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2020
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

(function() {
	if (!OCA.FromMail) {
		/**
		 * @namespace
		 */
		OCA.FromMail = {};
	}

	OCA.FromMail.Admin = {

		collection: [],

		init: function() {
			var self = this;

			self.getMailbox();

			var create = $('#frommail_create');
			create.on('click', function() {
				var address = $('#frommail_address').val();
				var password = $('#frommail_password').val();

				$('#frommail_address').val('')
				$('#frommail_password').val('')
				if (address === '') {
					return;
				}

				self.createMailbox(address, password);
			});
		},


		getMailbox: function() {
			var self = this;
			$.ajax({
				method: 'GET',
				url: OC.generateUrl('/apps/files_frommail/admin/mailbox')
			}).done(function(res) {
				self.collection = res;
				self.displayMailbox();
			});
		},


		displayMailbox: function() {
			if (this.collection === []) {
				return;
			}

			var self = this;
			var list = $('#frommail_list');
			list.empty();

			this.collection.forEach(function(item) {
				var mailbox = item.address
				if (item.password !== undefined && item.password !== '') {
					mailbox += ' :' + item.password;
				}

				var entry = $('<div>', {class: 'frommail-input'});
				entry.text(mailbox + ' ');
				var button = $('<a>', {
					id: 'frommail_submit',
					class: 'button',
					'data-mailbox': item.address
				}).append($('<span>').text('Delete'));
				button.on('click', function() {
					self.deleteMailbox(item.address);
				});
				entry.append(button);
				list.prepend(entry);
			});

		},


		createMailbox: function(mailbox, password) {
			var self = this;
			$.ajax({
				method: 'POST',
				url: OC.generateUrl('/apps/files_frommail/admin/mailbox'),
				data: {
					address: mailbox,
					password: password
				}
			}).done(function(res) {
				self.getMailbox();
			});

		},


		deleteMailbox: function(mailbox) {
			var self = this;
			$.ajax({
				method: 'DELETE',
				url: OC.generateUrl('/apps/files_frommail/admin/mailbox'),
				data: {address: mailbox}
			}).done(function(res) {
				self.getMailbox();
			});
		}

	};
})();

$(document).ready(function() {
	OCA.FromMail.Admin.init();
});

