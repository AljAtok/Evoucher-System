$(document).ready(function () {
	// Ensure DataTables responsive recalculation on tab show

	$(document).on("contextmenu", function (e) {
		// e.preventDefault();
	});

	$(window).resize(function (e) {
		var target = $(e.target).attr("href");
		var $table = $(target).find(".data-table").first();

		if ($table.length) {
			if ($.fn.DataTable.isDataTable($table)) {
				$table.DataTable().destroy();
			}
			if (!$.fn.DataTable.isDataTable($table)) {
				load_normal_datatables($table);
			}
			$table.DataTable().columns.adjust();
		}
	});

	$('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
		var target = $(e.target).attr("href");
		var $table = $(target).find(".data-table").first();

		if ($table.length) {
			if ($.fn.DataTable.isDataTable($table)) {
				$table.DataTable().destroy();
			}
			if (!$.fn.DataTable.isDataTable($table)) {
				load_normal_datatables($table);
			}
			$table.DataTable().columns.adjust().draw();

			// Add search handler for non-tab tables
			var table = $table.DataTable();
			table.on("search.dt", function () {
				setTimeout(function () {
					table.columns.adjust();
				}, 100);
			});

			table.on("destroy.dt", function () {
				table.off("search.dt");
			});
		}
	});

	// Initialize .data-table tables NOT inside a tab
	$(".data-table").each(function () {
		var $table = $(this);
		if (!$table.closest(".tab-pane").length) {
			if ($.fn.DataTable.isDataTable($table)) {
				$table.DataTable().destroy();
			}
			load_normal_datatables($table);

			// Add search handler for non-tab tables
			var table = $table.DataTable();
			table.on("search.dt", function () {
				setTimeout(function () {
					table.columns.adjust();
				}, 50);
			});

			table.on("destroy.dt", function () {
				table.off("search.dt");
			});
		}
	});
	// Initialize .data-table in the active tab
	var $tabTable = $(".tab-pane.active").find(".data-table").first();
	if ($tabTable.length) {
		if ($.fn.DataTable.isDataTable($tabTable)) {
			$tabTable.DataTable().destroy();
		}
		load_normal_datatables($tabTable);
		$tabTable.DataTable().columns.adjust().draw();

		// Auto-resize DataTable columns on search
		var table = $tabTable.DataTable();

		table.on("search.dt", function () {
			// Only adjust columns without full redraw to preserve expand/collapse functionality
			setTimeout(function () {
				table.columns.adjust();
			}, 50);
		});

		// Clean up event handlers on DataTable destroy
		table.on("destroy.dt", function () {
			table.off("search.dt");
		});
	}

	const baseUrl = $("#base_url").val();

	let url = document.location.toString();
	if (url.match("#")) {
		$('.nav-tabs a[href="#' + url.split("#")[1] + '"]').tab("show");
	}

	// $(".navbar-toggler").click(function () {
	// 	$("nav").toggleClass("overflow-hidden");
	// });

	if (url.match("#")) {
		$('.nav-pills a[href="#' + url.split("#")[1] + '"]').tab("show");
	}

	if (
		window.location.hash === "#nav-dash-credit" ||
		window.location.hash === "#nav-dash-non-credit" ||
		window.location.hash === "#nav-dash-inventory"
	) {
		window.location.href = baseUrl;
	}

	$(".nav-tabs a").on("shown.bs.tab", function (e) {
		window.location.hash = e.target.hash;
		// window.dispatchEvent(new Event("resize"));
	});

	$(".nav-pills a").on("shown.bs.tab", function (e) {
		window.location.hash = e.target.hash;
	});

	// console.log(baseUrl);
	// if (
	// 	baseUrl.indexOf("web-pos-redeem") !== -1 ||
	// 	baseUrl.indexOf("redemmption") !== -1 ||
	// 	baseUrl.indexOf("redeem") !== -1
	// ) {
	// 	var myModal = new bootstrap.Modal(
	// 		document.getElementById("modal-show-how-tos")
	// 	);
	// 	myModal.show(); // Auto open modal
	// }

	var modalElement = document.getElementById("modal-show-how-tos");
	if (modalElement) {
		// Check if modal exists
		var myModal = new bootstrap.Modal(modalElement);
		myModal.show();
	}

	// $(".modal").on("shown.bs.modal", function () {
	// 	$(this).find(".close").html('<i class="fas fa-times-circle"></i>'); // Replace with your icon
	// });

	$(".modal-header .close").replaceWith(
		'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="fas fa-times-circle"></i></button>'
	);

	let forms = document.getElementsByClassName("needs-validation");
	let validation = Array.prototype.filter.call(forms, function (form) {
		let submitButton = $(form).find('button[type="submit"]');
		form.addEventListener(
			"submit",
			function (event) {
				toggleButtonLoading(submitButton);
				if (form.checkValidity() === false) {
					toggleButtonLoading(submitButton, "Save");
					event.preventDefault();
					event.stopPropagation();
				}
				form.classList.add("was-validated");
			},
			false
		);
	});

	formEvents();
	initCouponElements();

	// load_normal_datatables($(".data-table"));

	$(".per-page").on("change", function () {
		window.location = $(this).val();
	});

	// $(document).on("select2:open", function (e) {
	// 	document.querySelector(".select2-search__field").focus();
	// });

	function formEvents() {
		$(".coupon-category").change((event) => {
			getHolderType(event.target);
		});

		$(".coupon-scope").change((event) => {
			// alert($(event.target).val());
			// alert($(event.target).find("option:selected").text());
			if ($(event.target).val() == "") {
				$(event.target)
					.parents("form")
					.find(".coupon-bc > option")
					.prop("selected", false);
				$(event.target).parents("form").find(".coupon-bc").trigger("change");
				$(event.target).parents("form").find('[name="scope_masking"]').val("");
			} else {
				getBusinessCenter(event.target);
			}
		});

		$(".voucher-value").change((event) => {
			let voucherQty = $(event.target)
				.parents("form")
				.find('[name="product_coupon_qty"]');
			computeTotalValue(voucherQty, event.target);
		});

		$('[name="allocation_count"]').change((event) => {
			let bcSelected = $(event.target).parents("form").find('[name="bc[]"]');
			computeTotalQty(event.target, bcSelected);
		});

		$(".holder-type").change((event) => {
			let couponCategory = $(event.target)
				.parents("form")
				.find(".coupon-category");
			getAditionalFields(couponCategory, event.target);
		});

		$(".validate-contact").keyup((event) => {
			validateContact($(event.target));
		});

		$(".validate-email").keyup((event) => {
			validateEmail($(event.target));
		});

		$('[name="attachment[]"]').on("change", function (e) {
			let uploadCount = e.target.files.length;
			let fileName =
				uploadCount > 1 ? uploadCount + " Files" : e.target.files[0].name;
			$(this).next(".custom-file-label").html(fileName);
		});
	}
	$('[name="product_coupon_qty"]').change((event) => {
		$(".voucher-value").trigger("change");
		$('[name="allocation_count"]').trigger("change");
	});

	function computeTotalValue(voucherQtyEl, voucherValueEl) {
		const voucherValue = $(voucherValueEl).val();
		const voucherQty = $(voucherQtyEl).val();
		const total = voucherQty * voucherValue;
		// console.log(voucherQty+" "+voucherValue);
		let totalValue = $(voucherQtyEl)
			.parents("form")
			.find('[name="total-voucher-value"]');
		$(totalValue).val(total);
		showInfo(
			"The total paid amount for this transaction is " + total + " pesos"
		);
	}

	function computeTotalQty(allocationQtyEl, bcSelectedEl) {
		const bcSelected = $(bcSelectedEl).val();
		const allocationQty = $(allocationQtyEl).val();
		if (bcSelected.indexOf("nationwide") === -1) {
			var bcSelectedCount = 0;
			if (bcSelected) {
				bcSelectedCount = bcSelected.length;
			}
			const total = bcSelectedCount * allocationQty;
			// console.log(voucherQty+" "+voucherValue);
			let totalValue = $(allocationQtyEl)
				.parents("form")
				.find('[name="product_coupon_qty"]');
			$(totalValue).val(total);
			showInfo(
				"The transaction total qty was change to " +
					total +
					" due to " +
					bcSelectedCount +
					" selected BCs & allocated " +
					allocationQty +
					" qty each!"
			);
		}
	}

	function initCouponElements() {
		// $("select").select2({
		// 	theme: "bootstrap4",
		// 	dropdownPosition: "below",
		// 	// dropdownParent: $("div.form-group"),
		// 	// dropdownPosition: "auto",
		// 	// dropdownPosition: $("div"),
		// });

		$("select").each(function () {
			let $select = $(this);
			let $parent = $select.closest(".form-group");

			// Only apply Select2 if needed (e.g., has a certain class or name)
			if ($select.hasClass("holder-type")) {
			}
			$select.select2({
				theme: "bootstrap4",
				// dropdownPosition: "below",
				dropdownParent: $parent,
			});
		});

		var element_select = '[name="upd_customer_id"]';
		var $el = $(element_select);

		if (!$el.prop("disabled")) {
			var select2Instance = $el.data("select2");
			if (
				typeof $el.select2 === "function" &&
				$el.hasClass("select2-hidden-accessible") &&
				select2Instance &&
				typeof select2Instance.destroy === "function"
			) {
				select2Instance.destroy();
			}
			$el.select2({
				theme: "bootstrap4",
				dropdownPosition: "below",
				dropdownParent: $("#customer-select-parent2"),
				tags: true, // Allow new entries
				minimumInputLength: 1, // Ensure search box appears
				// minimumResultsForSearch: 0, // Always show search results
				language: {
					noResults: function () {
						return "No results found. Press Enter to add.";
					},
				},
				escapeMarkup: function (markup) {
					return markup; // Allow HTML
				},
				createTag: function (params) {
					var term = $.trim(params.term);

					// Prevent adding empty or duplicate values
					if (term === "") {
						return null;
					}

					var exists = false;
					$(element_select + " option").each(function () {
						if ($(this).text().toLowerCase().includes(term.toLowerCase())) {
							exists = true;
							return false; // Stop loop early
						}
					});

					// If results exist, don't show "No results found"
					return exists ? null : { id: term, text: term, newTag: true };
				},
				templateResult: function (data) {
					if (data.newTag) {
						return $(
							'<span class="select2-no-results">No results found. Press Enter to add.</span>'
						);
					}
					return data.text;
				},
			});
		}
		// ...rest of your function code...

		var element_select = '[name="customer_id"]';
		if ($(element_select).data("select2")) {
			$(element_select).select2("destroy");
			$(element_select).select2({
				theme: "bootstrap4",
				dropdownPosition: "below",
				dropdownParent: $("#customer-select-parent"),
				tags: true, // Allow new entries
				minimumInputLength: 1, // Ensure search box appears
				// minimumResultsForSearch: 0, // Always show search results
				language: {
					noResults: function () {
						return "No results found. Press Enter to add.";
					},
				},
				escapeMarkup: function (markup) {
					return markup; // Allow HTML
				},
				createTag: function (params) {
					var term = $.trim(params.term);

					// Prevent adding empty or duplicate values
					if (term === "") {
						return null;
					}

					var exists = false;
					$(element_select + " option").each(function () {
						if ($(this).text().toLowerCase().includes(term.toLowerCase())) {
							exists = true;
							return false; // Stop loop early
						}
					});

					// If results exist, don't show "No results found"
					return exists ? null : { id: term, text: term, newTag: true };
				},
				templateResult: function (data) {
					if (data.newTag) {
						return $(
							'<span class="select2-no-results">No results found. Press Enter to add.</span>'
						);
					}
					return data.text;
				},
			});
		}

		// $('[name="DataTables_Table_0_length"]').each(function () {
		// 	if (
		// 		typeof $(this).select2 === "function" &&
		// 		$(this).hasClass("select2-hidden-accessible") &&
		// 		$(this).data("select2") != null
		// 	) {
		// 		try {
		// 			$(this).select2("destroy");
		// 		} catch (e) {
		// 			// Ignore error if not initialized
		// 		}
		// 	}
		// 	console.log(this);
		// });

		if ($('[name="DataTables_Table_0_length"]').data("select2")) {
			$('[name="DataTables_Table_0_length"]').select2("destroy");
		}
		if ($('[name="DataTables_Table_1_length"]').data("select2")) {
			$('[name="DataTables_Table_1_length"]').select2("destroy");
		}
		if ($('[name="DataTables_Table_2_length"]').data("select2")) {
			$('[name="DataTables_Table_2_length"]').select2("destroy");
		}
		if ($('[name="DataTables_Table_3_length"]').data("select2")) {
			$('[name="DataTables_Table_3_length"]').select2("destroy");
		}

		$('[type="number"]').on("input", function () {
			this.value = this.value.replace(/[^0-9]/g, ""); // Removes non-numeric characters
		});

		$(".date-range")
			.daterangepicker({
				alwaysShowCalendars: true,
				autoUpdateInput: false,
				drops: "up",
				locale: {
					cancelLabel: "Clear",
				},
			})
			.on("apply.daterangepicker", function (ev, picker) {
				$(this).val(
					picker.startDate.format("MM/DD/YYYY") +
						" - " +
						picker.endDate.format("MM/DD/YYYY")
				);
			})
			.on("cancel.daterangepicker", function (ev, picker) {
				$(this).val("");
			});
	}

	// $(".datepicker").datepicker({
	// 	clearBtn: true,
	// 	format: "mm/dd/yyyy",
	// 	autoclose: true,
	// 	viewMode: "days",
	// 	minViewMode: "days",
	// 	startDate: "01/01/2010",
	// 	immediateUpdates: true,
	// 	todayHighlight: true,
	// 	daysOfWeekHighlighted: ["00"],
	// });
	$(".datepicker").datepicker({
		uiLibrary: "bootstrap4",
		format: "mm/dd/yyyy",
		startDate: "01/01/2010",
		// value: startDate,
	});

	$(".datepicker-icon").click(function () {
		$(".datepicker").datepicker("show");
	});

	$.each($(".table-export"), function () {
		const table = $(this);
		const btn = $(
			'<button class="btn btn-primary btn-sm mb-3">Export Excel</button>'
		);
		$(btn)
			.insertBefore(table)
			.click(() => {
				const sheetName = $(table).data("sheet-name");
				const fileName = $(table).data("file-name");
				exportTable(table.get(0), sheetName, fileName);
			});

		function exportTable(table, sheetName, fileName, type = "xlsx", fn, dl) {
			let elt = table;
			let wb = XLSX.utils.table_to_book(elt, { sheet: sheetName });
			return dl
				? XLSX.write(wb, { bookType: type, bookSST: true, type: "base64" })
				: XLSX.writeFile(wb, fn || fileName + "." + (type || "xlsx"));
		}
	});

	$("#sidebar").mCustomScrollbar({
		theme: "minimal",
		scrollInertia: 0,
	});

	$("#sidebarCollapse").on("click", function () {
		// open or close navbar
		$("#sidebar").toggleClass("active");
		// close dropdowns
		$(".collapse.in").toggleClass("in");
		// and also adjust aria-expanded attributes we use for the open/closed arrows
		// in our CSS
		$("a[aria-expanded=true]").attr("aria-expanded", "false");
		$("#content").toggleClass("active");
		// $("nav").toggleClass("overflow-hidden");
	});

	function showError(message) {
		Lobibox.notify("error", {
			size: "mini",
			position: "top right",
			msg: message,
			sound: false,
			icon: "fas fa-exclamation-circle",
			delay: 3000,
		});
	}

	function showSuccess(message) {
		Lobibox.notify("success", {
			size: "mini",
			position: "top right",
			msg: message,
			sound: false,
			icon: "fas fa-check-circle",
			delay: 3000,
		});
	}

	function showInfo(message) {
		Lobibox.notify("info", {
			size: "mini",
			position: "top right",
			msg: message,
			sound: false,
			icon: "fas fa-exclamation-circle",
			delay: 6000,
		});
	}

	function removeValidationClass(element) {
		if (element.hasClass("is-valid")) {
			element.removeClass("is-valid");
		}

		if (element.hasClass("is-invalid")) {
			element.removeClass("is-invalid");
		}
	}

	function toggleValidationClass(result, element) {
		if (result) {
			if (!element.hasClass("is-valid")) {
				if (element.hasClass("is-invalid")) {
					element.removeClass("is-invalid");
				}
				element.addClass("is-valid");
			}
		} else {
			if (!element.hasClass("is-invalid")) {
				if (element.hasClass("is-valid")) {
					element.removeClass("is-valid");
				}
				element.addClass("is-invalid");
			}
		}
	}

	$(document).on("click", ".edit", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		let url = $(this).attr("data-url");
		$.ajax({
			url: baseUrl + url + id,
			data: { id: id },
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				if (parse_response["result"] == 1) {
					$("#modal-edit").find(".modal-body").html(parse_response["html"]);
					$("#modal-edit").modal({ show: true });
					initCouponElements();
					formEvents();
					$("#modal-edit").find(".select2").trigger("click");
				} else {
					console.log("Error please contact your administrator.");
				}
			},
		});
	});

	$(document).on("click", ".reset", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		let url = $(this).attr("data-url");
		$.ajax({
			url: baseUrl + url + id,
			data: { id: id },
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				if (parse_response["result"] == 1) {
					$("#modal-reset").find(".modal-body").html(parse_response["html"]);
					$("#modal-reset").modal({ show: true });
					initCouponElements();
					formEvents();
				} else {
					console.log("Error please contact your administrator.");
				}
			},
		});
	});

	$(document).on("click", ".view-details", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		let url = $(this).attr("data-url");
		$.ajax({
			url: baseUrl + url + id,
			data: { id: id },
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				if (parse_response["result"] == 1) {
					$("#modal-view").find(".modal-body").html(parse_response["html"]);
					$("#modal-view").modal({ show: true });
					initCouponElements();
					formEvents();
				} else {
					console.log("Error please contact your administrator.");
				}
			},
		});
	});

	$(document).on("click", ".toggle-inactive", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#activate-form").find("#id").val(id);
		$("#modal-active").modal({ show: true });
	});

	$(document).on("click", ".toggle-active", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#deactivate-form").find("#id").val(id);
		$("#modal-deactivate").modal({ show: true });
	});

	function getAditionalFields(categoryElement, holderTypeElement) {
		const category = $(categoryElement);
		const holderType = $(holderTypeElement);
		const url =
			baseUrl +
			"/get-additional-fields/" +
			category.val() +
			"/" +
			holderType.val();
		let addFieldsContainer = $(categoryElement)
			.parents("form")
			.find(".additional-field");
		$(addFieldsContainer).load(url, function () {
			formEvents();
			initCouponElements();
		});
	}

	// $('#loader-div').removeClass('loaded');
	$("#loader-div").addClass("loaded");

	function getSpinner() {
		return '<i class="fas fa-circle-notch fa-spin"></i>&nbsp;&nbsp;&nbsp;';
	}

	function toggleButtonLoading(element, defaultText = "") {
		let elementDisabledValue = $(element).prop("disabled");
		let spinner = $(
			'<div class="loader-container text-light text-center">' +
				'<span class="fa fa-circle-notch fa-spin"></span>' +
				// + "<span>&nbsp;Loading</span>"
				"</div>"
		);

		$(element).empty();
		if (elementDisabledValue) {
			$(element).prop("disabled", !elementDisabledValue).append(defaultText);
		} else {
			$(element).prop("disabled", !elementDisabledValue).append(spinner);
		}
	}

	$(document).on("click", ".edit-standard-coupon", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$.ajax({
			url: baseUrl + "/modal-standard-coupon/" + id,
			data: { id: id },
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				if (parse_response["result"] == 1) {
					$("#modal-edit-standard-coupon")
						.find(".modal-body")
						.html(parse_response["html"]);
					$("#modal-edit-standard-coupon").modal({ show: true });
					initCouponElements();
					formEvents();
				} else {
					console.log("Error please contact your administrator.");
				}
			},
		});
	});

	$(document).on("click", ".edit-product-coupon", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$.ajax({
			url: baseUrl + "/modal-product-coupon/" + id,
			data: { id: id },
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				if (parse_response["result"] == 1) {
					$("#modal-edit-product-coupon")
						.find(".modal-body")
						.html(parse_response["html"]);
					$("#modal-view-prod-coupon-details").modal("hide");
					$("#modal-view-prod-coupon-details").find(".modal-body").empty();
					$("#modal-edit-product-coupon").modal({ show: true });
					initCouponElements();
					formEvents();
				} else {
					console.log("Error please contact your administrator.");
				}
			},
		});
	});

	$(document).on("click", ".edit-transaction-coupon", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$.ajax({
			url: baseUrl + "/modal-transaction-coupon/" + id,
			data: { id: id },
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				if (parse_response["result"] == 1) {
					$("#modal-edit-transaction-coupon")
						.find(".modal-body")
						.html(parse_response["html"]);
					$("#modal-edit-transaction-coupon").modal({ show: true });
					initCouponElements();
					formEvents();
				} else {
					console.log("Error please contact your administrator.");
				}
			},
		});
	});

	$(document).on("click", ".view-attachments", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		let url = $(this).attr("data-url");
		$.ajax({
			url: baseUrl + url + id,
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				if (parse_response["result"] == 1) {
					$("#modal-view").find(".modal-body").html(parse_response["html"]);
					$("#modal-view").modal({ show: true });
				} else {
					console.log("Error please contact your administrator.");
				}
			},
		});
	});

	$(document).on("click", ".toggle-inactive-coupon", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#activate-coupon").find("#id").val(id);
		$("#modal-active-coupon").modal({ show: true });
	});

	$(document).on("click", ".toggle-active-coupon", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#deactivate-coupon").find("#id").val(id);
		$("#modal-deactivate-coupon").modal({ show: true });
	});

	$(document).on("click", ".approve-coupon", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#approve-coupon").find("#id").val(id);
		const url = baseUrl + "/get-invoice-coupon-field/" + id;
		let invoiceContainer = $("#approve-coupon").find(".invoice-container");
		$(invoiceContainer).load(url, function () {
			formEvents();
			$("#modal-approve-coupon").modal({ show: true });
		});
	});

	$(document).on("click", ".approve-transaction", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#approve-transaction").find("#id").val(id);
		let container = $("#approve-transaction").find(".invoice-field-container");
		const url = baseUrl + "/get-invoice-trans-field/" + id;
		$(container).load(url, function () {
			formEvents();
			$("#modal-approve-transaction")
				.find(".modal-title")
				.html("<strong>Approve Transaction</strong>");
			$("#modal-approve-transaction")
				.find(".modal-msg")
				.html("<strong>Are you sure to approve this Transaction?</strong>");
			$("#modal-approve-transaction").modal({ show: true });
		});
	});

	$(document).on("click", ".publish-transaction", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#publish-transaction").find("#id").val(id);

		$("#modal-publish-transaction")
			.find(".modal-title")
			.html("<strong>Publish Transaction</strong>");
		$("#modal-publish-transaction")
			.find(".modal-msg")
			.html("<strong>Are you sure to publish this Transaction?</strong>");
		$("#modal-publish-transaction").modal({ show: true });
	});

	$(document).on("click", ".edit-approve-transaction", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#approve-transaction").find("#id").val(id);
		let new_link = baseUrl + "/update-approve-transaction";
		$("#approve-transaction").attr("action", new_link);
		let container = $("#approve-transaction").find(".invoice-field-container");
		const url = baseUrl + "/get-invoice-trans-field/" + id;
		$(container).load(url, function () {
			formEvents();
			$("#modal-approve-transaction")
				.find(".modal-title")
				.html("<strong>Edit Approval Inputs</strong>");
			$("#modal-approve-transaction").find(".modal-msg").html("");
			$("#modal-approve-transaction").modal({ show: true });
		});
	});

	$(document).on("click", ".return-pending-transaction", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#return-pending-transaction").find("#id").val(id);

		$("#modal-return-pending-transaction").modal({ show: true });
	});

	$(document).on("click", ".return-first-approve-transaction", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#return-first-approve-transaction").find("#id").val(id);

		$("#modal-return-first-approve-transaction").modal({ show: true });
	});

	$(document).on("click", ".return-approve-transaction", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#return-approve-transaction").find("#id").val(id);

		$("#modal-return-approve-transaction").modal({ show: true });
	});

	$(document).on("click", ".first-approve-transaction", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#first-approve-transaction").find("#id").val(id);
		let new_link = baseUrl + "/first-approve-transaction";
		$("#first-approve-transaction").attr("action", new_link);
		let container = $("#first-approve-transaction").find(
			".payment-det-field-container"
		);
		const url = baseUrl + "/get-payment-det-trans-field/" + id;
		$(container).load(url, function () {
			initCouponElements();
			formEvents();
			$("#modal-first-approve-transaction")
				.find(".modal-title")
				.html("<strong>Approve Transaction</strong>");
			$("#modal-first-approve-transaction")
				.find(".modal-msg")
				.html("<strong>Are you sure to approve this Transaction?</strong>");
			$("#modal-first-approve-transaction").modal({ show: true });
		});
	});

	$(document).on("click", ".edit-first-approve-transaction", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#first-approve-transaction").find("#id").val(id);
		let new_link = baseUrl + "/update-first-approve-transaction";
		$("#first-approve-transaction").attr("action", new_link);
		let container = $("#first-approve-transaction").find(
			".payment-det-field-container"
		);
		const url = baseUrl + "/get-payment-det-trans-field/" + id;
		$(container).load(url, function () {
			initCouponElements();
			formEvents();
			$("#modal-first-approve-transaction")
				.find(".modal-title")
				.html("<strong>Edit Approval Inputs</strong>");
			$("#modal-first-approve-transaction").find(".modal-msg").html("");
			$("#modal-first-approve-transaction").modal({ show: true });
		});
	});

	$(document).on("change", '[name="payment_type_id"]', function (e) {
		e.preventDefault();
		// let id = $(this).attr("data-id");
		var id = $("#first-approve-transaction").find("#id").val()
			? $("#first-approve-transaction").find("#id").val()
			: 0;
		if (id == 0) {
			var id = $(".product-transaction").find("#id").val()
				? $(".product-transaction").find("#id").val()
				: 0;
		}
		let payment_type_id = $(this).val();

		let container = $("#first-approve-transaction").find(
			".payment-det-field-container"
		);
		const url =
			baseUrl + "/get-payment-det-trans-field/" + id + "/" + payment_type_id;
		$(container).load(url, function () {
			// formEvents();
			initCouponElements();
			// $("#modal-first-approve-transaction").modal({ show: true });
		});
		let container2 = $(".product-transaction").find(
			".payment-det-field-container"
		);
		$(container2).load(url, function () {
			// formEvents();
			initCouponElements();
		});
	});

	$(document).on("click", ".toggle-inactive-transaction", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#activate-transaction").find("#id").val(id);
		$("#modal-activate-transaction").modal({ show: true });
	});

	$(document).on("click", ".toggle-active-transaction", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#deactivate-transaction").find("#id").val(id);
		$("#modal-deactivate-transaction").modal({ show: true });
	});

	$(document).on("click", ".regenerate-pdf", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#restore-transaction").find("#id").val(id);
		$("#modal-restore-transaction").modal({ show: true });
	});

	$(document).on("click", ".archive-pdf", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#archive-transaction").find("#id").val(id);
		$("#modal-archive-transaction").modal({ show: true });
	});

	$(document).on("click", ".view-product-coupon-details", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$.ajax({
			url: baseUrl + "/modal-coupon-trans-details/" + id,
			data: { id: id },
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				if (parse_response["result"] == 1) {
					$("#modal-view-prod-coupon-details")
						.find(".modal-body")
						.html(parse_response["html"]);
					$("#modal-view-prod-coupon-details").modal({ show: true });
				} else {
					console.log("Error please contact your administrator.");
				}
			},
		});
	});

	$(document).ready(function () {
		if ($("#success-product-coupon-details").length > 0) {
			$("#success-product-coupon-details").modal({ show: true });
		}
	});

	$(document).on("click", "#coupon-verify-button", function (e) {
		e.preventDefault();
		let code = $('[name="code"]').val();
		toggleButtonLoading($(this));
		$("#loader-div").removeClass("loaded");
		$.ajax({
			url: baseUrl + "/web-validate-coupon",
			data: { code: code },
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				$("#message-box").empty();
				$("#message-box").html(parse_response["html"]);

				$("#loader-div").addClass("loaded");
			},
		});

		toggleButtonLoading($(this), "Verify");
	});

	$(document).on("click", "#coupon-redeem-button", function (e) {
		e.preventDefault();
		let code = $('[name="code"]').val();
		let contact = $('[name="contact"]').val();
		toggleButtonLoading($(this));
		$("#loader-div").removeClass("loaded");
		$.ajax({
			url: baseUrl + "/web-redeem-coupon",
			data: {
				code: code,
				contact: contact,
			},
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				$("#message-box").empty();
				$("#message-box").html(parse_response["html"]);

				$("#loader-div").addClass("loaded");
			},
		});
		toggleButtonLoading($(this), "Redeem");
	});

	$(document).on("click", "#coupon-redeem-button-new", function (e) {
		e.preventDefault();
		let code = $('[name="code"]').val();
		let store_code = $('[name="store-code"]').val();
		let crew_code = $('[name="crew-code"]').val();
		let user_id = $('[name="user_id"]').val();
		toggleButtonLoading($(this));

		$("#loader-div").removeClass("loaded");
		$.ajax({
			url: baseUrl + "/enhanced-web-redeem-coupon",
			data: {
				code: code,
				store_code: store_code,
				crew_code: crew_code,
				user_id: user_id,
			},
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				$("#message-box").empty();
				$("#message-box").html(parse_response["html"]);

				$("#loader-div").addClass("loaded");
			},
		});
		toggleButtonLoading($(this), "REDEEM AGAIN");
	});

	$(document).on("click", "#coupon-verify-button-new", function (e) {
		e.preventDefault();
		let code = $('[name="code"]').val();
		let store_code = $('[name="store-code"]').val();
		let crew_code = $('[name="crew-code"]').val();
		toggleButtonLoading($(this));
		$("#loader-div").removeClass("loaded");
		$.ajax({
			url: baseUrl + "/web-validate-coupon",
			data: { code: code },
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				$("#message-box").empty();
				$("#message-box").html(parse_response["html"]);

				$("#loader-div").addClass("loaded");
			},
		});

		toggleButtonLoading($(this), "VERIFY AGAIN");
	});

	$(document).on("click", "#coupon-redeem-button-emp", function (e) {
		e.preventDefault();
		let code = $('[name="code"]').val();
		let added_info = $('[name="added-info"]').val();
		let store_code = $('[name="store-code"]').val();
		let crew_code = $('[name="crew-code"]').val();
		let user_id = $('[name="user_id"]').val();
		toggleButtonLoading($(this));

		$("#loader-div").removeClass("loaded");
		$.ajax({
			url: baseUrl + "/enhanced-web-redeem-emp-coupon",
			data: {
				code: code,
				added_info: added_info,
				store_code: store_code,
				crew_code: crew_code,
				user_id: user_id,
			},
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				$("#message-box").empty();
				$("#message-box").html(parse_response["html"]);

				$("#loader-div").addClass("loaded");
			},
		});
		toggleButtonLoading($(this), "REDEEM AGAIN");
	});

	$(document).on("click", "#coupon-verify-button-emp", function (e) {
		e.preventDefault();
		let code = $('[name="code"]').val();
		let store_code = $('[name="store-code"]').val();
		let crew_code = $('[name="crew-code"]').val();
		toggleButtonLoading($(this));
		$("#loader-div").removeClass("loaded");
		$.ajax({
			url: baseUrl + "/web-validate-coupon",
			data: { code: code },
			method: "POST",
			success: function (response) {
				let parse_response = JSON.parse(response);
				$("#message-box").empty();
				$("#message-box").html(parse_response["html"]);

				$("#loader-div").addClass("loaded");
			},
		});

		toggleButtonLoading($(this), "VERIFY AGAIN");
	});

	valueTypeEvents();
	function valueTypeEvents() {
		$(document).on("change", '[name="value_type"]', function (e) {
			let elemText = $(this).find("option:selected").text();
			let maxValue = elemText == "PERCENTAGE" ? "100" : "999";
			let amountField = $(this).parents("form").find('[name="amount"]');
			$(amountField).attr("max", maxValue);
			$(amountField).keyup(function () {
				elemText = $('[name="value_type"]').find("option:selected").text();
				if (amountField.val().length == 3) {
					return false;
				} else {
					if (elemText == "PERCENTAGE") {
						if (amountField.val() > 100) {
							amountField.val(100);
							return false;
						}
					}
				}
			});
		});
	}

	function validateContact(element) {
		let elementValue = $(element).val();
		const baseUrl = $("#base_url").val();
		let url = baseUrl + "/check-contact-prefix";
		if ($(element).val().length == 11) {
			let data = {
				contact_number: elementValue,
			};
			$.ajax({
				method: "POST",
				url: url,
				data: data,
			}).done((response) => {
				let responseData = JSON.parse(response);
				toggleValidationClass(responseData.result, element);
			});
		} else if ($(element).val().length < 11 && elementValue != "") {
			toggleValidationClass(false, element);
		} else {
			removeValidationClass(element);
		}
	}

	function validateEmail(element) {
		let elementValue = $(element).val();
		const baseUrl = $("#base_url").val();
		let url = baseUrl + "/check-valid-email";
		let data = {
			email: elementValue,
		};

		if (elementValue != "") {
			$.ajax({
				method: "POST",
				url: url,
				data: data,
			}).done((response) => {
				let responseData = JSON.parse(response);
				toggleValidationClass(responseData.result, element);
			});
		} else {
			removeValidationClass(element);
		}
	}

	$(document).on("click", ".cancel-coupon", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#cancel-coupon").find("#id").val(id);
		$("#modal-cancel-coupon").modal({ show: true });
	});

	$(document).on("click", ".cancel-transaction", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#cancel-transaction").find("#id").val(id);
		$("#modal-cancel-transaction").modal({ show: true });
	});

	function getHolderType(element) {
		const category = $(element);

		const url = baseUrl + "/get-holder-type/" + category.val();
		let addHolderOption = $(element)
			.parents("form")
			.find('[name="holder_type"]');
		$(addHolderOption).load(url);
	}

	function getBusinessCenter(element) {
		const scope = $(element);
		var soope_masking_text = scope.find("option:selected").text();
		const url = baseUrl + "/get-scope-bc/" + scope.val();
		let addHolderOption = $(element).parents("form").find('[name="bc[]"]');

		let scope_masking = $(element)
			.parents("form")
			.find('[name="scope_masking"]');

		// if(scope_masking_text != 'Select...'){
		// }
		$(scope_masking).val(soope_masking_text);
		$(addHolderOption).load(url);
	}

	$(document).on("click", ".pay-coupon", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#pay-coupon").find("#id").val(id);
		const url = baseUrl + "/get-invoice-coupon-field/" + id;
		let invoiceContainer = $("#pay-coupon").find(".invoice-container");
		$(invoiceContainer).load(url, function () {
			$("#modal-pay-coupon").modal({ show: true });
		});
	});

	$(document).on("click", ".pay-transaction", function (e) {
		e.preventDefault();
		let id = $(this).attr("data-id");
		$("#pay-transaction").find("#id").val(id);
		let container = $("#pay-transaction").find(".pay-field-container");
		const url = baseUrl + "/get-pay-trans-field/" + id;
		$(container).load(url, function () {
			$("#modal-pay-transaction").modal({ show: true });
		});
	});

	/*Redeem Logs*/

	var redeem_logs_tbl = load_datatables("#tbl-redeem-logs");

	$("#redeem-logs-calendar").daterangepicker();

	$("#dashboard-filter-calendar").daterangepicker();

	$("#redeem-logs-calendar").on("change", function () {
		var date = $(this).val();
		console.log(date);
		if (date) {
			var link = baseUrl + "/download-redeemed-logs?date=" + date;
			$("#download-redeemed-logs").attr("href", link);

			$("#loader-div").removeClass("loaded");
			$.ajax({
				url: baseUrl + "/get-redeem-logs-data",
				data: { date: date },
				method: "POST",
				success: function (response) {
					var parse_response = JSON.parse(response);

					if (parse_response["result"] == 1) {
						var logs_tbl = parse_response["tbl_logs"];

						redeem_logs_tbl.destroy();

						$("#tbl-redeem-logs > tbody").empty();
						$("#tbl-redeem-logs > tbody").append(logs_tbl);

						redeem_logs_tbl = load_datatables("#tbl-redeem-logs");

						$("#loader-div").addClass("loaded");
					} else {
						var msg = parse_response["msg"];
						showError(msg);
						$("#loader-div").addClass("loaded");
					}
				},
			});

			$("#loader-div").removeClass("loaded");
		} else {
			showError("Please pick Date.");
		}
	});

	$(".used-date-type").change((event) => {
		$("#used-voucher-calendar").trigger("change");
	});

	var used_voucher_tbl = load_datatables(
		"#tbl-used-voucher",
		baseUrl + "/used_voucher_grid",
		true,
		"POST"
	);

	$("#used-voucher-calendar").daterangepicker();

	$("#used-voucher-calendar").on("change", function () {
		var date = $(this).val();
		// alert(date);
		var date_type = $(".used-date-type").val();
		// alert(date_type);

		if (date && date_type) {
			get_coupon_trans_data(
				date,
				date_type,
				0,
				".used-coupon-transaction-header-ids"
			);
		} else {
			showError("Please pick Date.");
		}
	});

	$("#used-voucher-form").on("submit", function (event) {
		event.preventDefault();
		var formID = "#used-voucher-form";
		var modalID = "#modal-used-voucher";

		$("#loader-div").removeClass("loaded");
		$.ajax({
			url: baseUrl + "/get-used-voucher-data",
			method: "POST",
			data: $(formID).serialize(),
			dataType: "json",
			success: function (data) {
				// var used_tbl = parse_response['tbl_used'];
				var used_tbl = data.tbl_used;

				used_voucher_tbl.ajax
					.url(
						baseUrl +
							"/used_voucher_grid?start_date=" +
							data.start_date +
							"&end_date=" +
							data.end_date +
							"&date_type=" +
							data.date_filter_type +
							"&coupon_transaction_header_id=" +
							data.coupon_transaction_header_ids
					)
					.load();
				used_voucher_tbl.columns.adjust();
				$("#used_coupon_transaction_header_ids").val(
					data.coupon_transaction_header_ids
				);

				$(modalID).modal("hide");

				$("#loader-div").addClass("loaded");
			},
			error: function (xhr, textStatus, errorThrown) {
				showError("Error in Saving!");
				console.log(xhr.responseText);
				$("#loader-div").addClass("loaded");
			},
		});
	});

	$("#download-used-voucher").on("click", function () {
		var date = $("#used-voucher-calendar").val();
		var date_type = $(".used-date-type").val();
		var used_coupon_transaction_header_ids = $(
			"#used_coupon_transaction_header_ids"
		).val();

		if (date && date_type) {
			var url =
				baseUrl +
				"/download-used-voucher-data?date=" +
				date +
				"&date_type=" +
				date_type +
				"&coupon_transaction_header_id=" +
				used_coupon_transaction_header_ids;

			// printWindow = window.open( url ,"_self");
			printWindow = window.open(url, "_blank");
		} else {
			showWarning("Please apply filter first...");
		}
	});

	$(".unused-date-type").change((event) => {
		$("#unused-voucher-calendar").trigger("change");
	});

	var unused_voucher_tbl = load_datatables(
		"#tbl-unused-voucher",
		baseUrl + "/unused_voucher_grid",
		true,
		"POST"
	);

	$("#unused-voucher-calendar").daterangepicker();

	$("#unused-voucher-calendar").on("change", function () {
		var date = $(this).val();

		var date_type = $(".used-date-type").val();

		if (date && date_type) {
			get_coupon_trans_data(
				date,
				date_type,
				1,
				".unused-coupon-transaction-header-ids"
			);
		} else {
			showError("Please pick Date.");
		}
	});

	$("#unused-voucher-form").on("submit", function (event) {
		event.preventDefault();
		var formID = "#unused-voucher-form";
		var modalID = "#modal-unused-voucher";

		$("#loader-div").removeClass("loaded");
		$.ajax({
			url: baseUrl + "/get-unused-voucher-data",
			method: "POST",
			data: $(formID).serialize(),
			dataType: "json",
			success: function (data) {
				var unused_tbl = data.tbl_unused;

				unused_voucher_tbl.ajax
					.url(
						baseUrl +
							"/unused_voucher_grid?start_date=" +
							data.start_date +
							"&end_date=" +
							data.end_date +
							"&date_type=" +
							data.date_filter_type +
							"&coupon_transaction_header_id=" +
							data.coupon_transaction_header_ids
					)
					.load();
				unused_voucher_tbl.columns.adjust();
				$("#unused_coupon_transaction_header_ids").val(
					data.coupon_transaction_header_ids
				);

				$(modalID).modal("hide");

				$("#loader-div").addClass("loaded");
			},
			error: function (xhr, textStatus, errorThrown) {
				showError("Error in Saving!");
				console.log(xhr.responseText);
				$("#loader-div").addClass("loaded");
			},
		});
	});

	$("#download-unused-voucher").on("click", function () {
		var date = $("#unused-voucher-calendar").val();
		var date_type = $(".unused-date-type").val();
		var unused_coupon_transaction_header_ids = $(
			"#unused_coupon_transaction_header_ids"
		).val();

		if (date && date_type) {
			var url =
				baseUrl +
				"/download-unused-voucher-data?date=" +
				date +
				"&date_type=" +
				date_type +
				"&coupon_transaction_header_id=" +
				unused_coupon_transaction_header_ids;

			printWindow = window.open(url, "_self");
		} else {
			showWarning("Please apply filter first...");
		}
	});

	function get_coupon_trans_data(date, date_type, unused, targ_el) {
		$("#loader-div").removeClass("loaded");
		$.ajax({
			url: baseUrl + "/get-coupon-trans-data",
			data: { date: date, date_type: date_type, unused: unused },
			method: "POST",
			success: function (response) {
				var parse_response = JSON.parse(response);

				if (parse_response["result"] == 1) {
					var coupon_trans = parse_response["coupon_trans"];

					$(targ_el).empty();
					$(targ_el).append(coupon_trans);

					$("#loader-div").addClass("loaded");
				} else {
					var msg = parse_response["msg"];
					showError(msg);
					$("#loader-div").addClass("loaded");
				}
			},
		});

		$("#loader-div").removeClass("loaded");
	}

	$(".coupon-bc").on("select2:select", function (e) {
		var data = e.params.data.text;
		if (data == "Select All") {
			$(".coupon-bc > option").prop("selected", "selected");
			$(".coupon-bc").trigger("change");
		}
		$(".coupon-bc option[value=-1]")
			.prop("selected", false)
			.parent()
			.trigger("change");
	});

	$(".unused-coupon-transaction-header-ids").on("select2:select", function (e) {
		var data = e.params.data.text;
		if (data == "Select All") {
			$(".unused-coupon-transaction-header-ids > option").prop(
				"selected",
				"selected"
			);
			$(".unused-coupon-transaction-header-ids").trigger("change");
		}
		$(".unused-coupon-transaction-header-ids option[value=-1]")
			.prop("selected", false)
			.parent()
			.trigger("change");
	});

	$(".used-coupon-transaction-header-ids").on("select2:select", function (e) {
		var data = e.params.data.text;
		if (data == "Select All") {
			$(".used-coupon-transaction-header-ids > option").prop(
				"selected",
				"selected"
			);
			$(".used-coupon-transaction-header-ids").trigger("change");
		}
		$(".used-coupon-transaction-header-ids option[value=-1]")
			.prop("selected", false)
			.parent()
			.trigger("change");
	});

	var today = new Date();
	var todayDate =
		today.getMonth() +
		1 +
		"/" +
		today.getDate() +
		"/" +
		today.getFullYear() +
		" " +
		today.getHours() +
		":" +
		today.getMinutes() +
		":" +
		today.getSeconds();

	function get_dashboard_datatables(
		table_id,
		table_url,
		fetching_type = "POST"
	) {
		var doc_title = "";
		var columnNos = [3, 5];
		var target_column_defs = [3, 4, 5];
		if (table_id == "#tbl_dashboard_non_credit") {
			var doc_title = document.title + " Non-Credit Trans Report";
		} else if (table_id == "#tbl_dashboard_cleared") {
			var doc_title = document.title + " Credit Cleared Trans Report";
		} else if (table_id == "#tbl_dashboard_receivables") {
			var doc_title = document.title + " Credit Receivables Trans Report";
		} else if (table_id == "#tbl_dashboard_monthly_all") {
			var doc_title = document.title + " Monthly Recon Report";
			var columnNos = [2, 3, 4, 5, 6, 8];
			var target_column_defs = [2, 3, 4, 5, 6, 7, 8];
		} else if (table_id == "#tbl_dashboard_transaction_all") {
			var doc_title = document.title + " Per Transaction Recon Report";
			var columnNos = [3, 4, 5, 6, 7, 9];
			var target_column_defs = [3, 4, 5, 6, 7, 8, 9];
		} else if (table_id == "#tbl_dashboard_customer_all") {
			var doc_title = document.title + " Per Customer Recon Report";
			var columnNos = [2, 3, 4, 5, 6, 8];
			var target_column_defs = [2, 3, 4, 5, 6, 7, 8];
		}

		var tbl_dashboard = $(table_id).DataTable({
			// "pagingType": "full",
			// dom: "Bfrtip",
			language: {
				emptyTable: "No data available",
				lengthMenu: "Display _MENU_ ",
				info: "Displaying _START_ to _END_ of _TOTAL_ entries",
				infoEmpty: "Displaying 0 to 0 of 0 entries",
				search: '<i class="fa fa-search" aria-hidden="true"></i>',
				paginate: {
					first: '<i class="fas fa-fast-backward"></i>',
					last: '<i class="fas fa-fast-forward"></i>',
					next: '<i class="fas fa-step-forward"></i>',
					previous: '<i class="fas fa-step-backward"></i>',
				},
			},
			processing: true,
			serverSide: false,
			order: [],
			columnDefs: [
				{
					targets: target_column_defs,
					className: "text-right",
				},
			],
			select: true,
			lengthMenu: [
				[10, 50, 100, 500, 1000, 5000, 10000],
				[10, 50, 100, 500, 1000, 5000, 10000],
			],
			// initComplete: function (settings) {
			// 	// var totalRecords = settings._iRecordsTotal;
			// 	var totalRecords =
			// 		fetching_type == "POST"
			// 			? settings._iRecordsDisplay
			// 			: settings._iRecordsTotal;

			// 	console.log("initComplete triggered for:", table_id); // Debugging

			// 	// var lengthLabel = $(table_id + "_length label");
			// 	var lengthLabel = $(table_id)
			// 		.closest(".dataTables_wrapper")
			// 		.find(".dataTables_length label");
			// 	lengthLabel.append("<span> " + totalRecords + " Total Entries</span>");
			// },
			ajax: {
				url: table_url,
				type: "POST",
			},
			buttons: [
				{
					// extend: "excelHtml5",
					extend: "excel",
					footer: true, // Ensure footer is included in export
					title: doc_title, // Title
					messageTop: "Run Date : " + todayDate,
					customize: function (xlsx) {
						var sheet = xlsx.xl.worksheets["sheet1.xml"];
						$("row:last c", sheet).attr("s", "2"); // Style last row (total)
					},
					exportOptions: {
						//columns: ':visible'
						columns: function (idx, data, node) {
							if ($(node).hasClass("noVis")) {
								return false;
							}
							return tbl_dashboard.column(idx).visible();
						},
					},
				},
			],
			drawCallback: function () {
				var api = this.api();

				columnNos.forEach(function (column_no) {
					var footerCell = api.column(column_no).footer();
					if (!footerCell) {
						console.warn("Footer cell not found for column:", column_no);
						return;
					}

					var total = api
						.column(column_no, { page: "current" })
						.data()
						.reduce(
							(a, b) => a + (parseFloat(b.toString().replace(/,/g, "")) || 0),
							0
						);

					$(footerCell).html(total.toLocaleString()); // Add comma formatting
				});

				let tableInfo = this.api().page.info(); // Use 'this.api()' to ensure access to correct instance
				let totalRecords = tableInfo.recordsTotal || tableInfo.recordsDisplay;
				let selectedLength = this.api().page.len(); // Selected lengthMenu value
				// let totalRecords = tableInfo.recordsTotal;
				// let displayedRecords = tableInfo.recordsDisplay;
				// console.log(table_id + " Displayed Records:" + displayedRecords);
				// console.log(table_id + " Total Records:" + totalRecords);

				var lengthLabel = $(table_id)
					.closest(".dataTables_wrapper")
					.find(".dataTables_length label");

				var lengthLablDesc =
					selectedLength > totalRecords
						? "<span> Entries</span>"
						: "<span>out of " + totalRecords + " Entries</span>";

				lengthLabel.find("span").remove(); // Remove any previous appended spans
				lengthLabel.append(lengthLablDesc);
			},
		});

		return tbl_dashboard;
	}

	function load_datatables(
		table_id,
		table_url = false,
		server_side = false,
		fetching_type = "POST"
	) {
		if (!$(table_id).length) {
			// console.log("Table not found: " + table_id);
			return;
		}
		if (table_url) {
			var tbl = $(table_id).DataTable({
				// "pagingType": "full",
				// dom: "Bfrtip",
				language: {
					emptyTable: "No data available",
					lengthMenu: "Display _MENU_ ",
					info: "Displaying _START_ to _END_ of _TOTAL_ entries",
					infoEmpty: "Displaying 0 to 0 of 0 entries",
					search: '<i class="fa fa-search" aria-hidden="true"></i>',
					paginate: {
						first: '<i class="fas fa-fast-backward"></i>',
						last: '<i class="fas fa-fast-forward"></i>',
						next: '<i class="fas fa-step-forward"></i>',
						previous: '<i class="fas fa-step-backward"></i>',
					},
				},
				// initComplete: function (settings) {
				// 	// var totalRecords = settings._iRecordsTotal;
				// 	var totalRecords =
				// 		fetching_type == "POST"
				// 			? settings._iRecordsDisplay
				// 			: settings._iRecordsTotal;

				// 	console.log("initComplete triggered for:", table_id); // Debugging

				// 	// var lengthLabel = $(table_id + "_length label");
				// 	var lengthLabel = $(table_id)
				// 		.closest(".dataTables_wrapper")
				// 		.find(".dataTables_length label");
				// 	lengthLabel.append(
				// 		"<span> " + totalRecords + " Total Entries</span>"
				// 	);
				// },
				drawCallback: function (settings) {
					var totalRecords =
						fetching_type == "POST"
							? settings._iRecordsDisplay
							: settings._iRecordsTotal;
					// console.log("Total Records:", totalRecords); // Debugging
					let selectedLength = this.api().page.len(); // Selected lengthMenu value

					var lengthLabel = $(table_id)
						.closest(".dataTables_wrapper")
						.find(".dataTables_length label");

					var lengthLablDesc =
						selectedLength > totalRecords
							? "<span> Entries</span>"
							: "<span>out of " + totalRecords + " Entries</span>";

					lengthLabel.find("span").remove(); // Remove any previous appended spans
					lengthLabel.append(lengthLablDesc);
				},
				processing: true,
				serverSide: server_side,
				order: [],
				select: true,
				lengthMenu: [
					[10, 50, 100, 500, 1000, 5000, 10000],
					[10, 50, 100, 500, 1000, 5000, 10000],
				],
				ajax: {
					url: table_url,
					type: fetching_type,
				},
			});
		} else {
			var tbl = $(table_id).DataTable({
				// "pagingType": "full",
				// dom: "Bfrtip",
				language: {
					emptyTable: "No data available",
					lengthMenu: "Display _MENU_ ",
					info: "Displaying _START_ to _END_ of _TOTAL_ entries",
					infoEmpty: "Displaying 0 to 0 of 0 entries",
					search: '<i class="fa fa-search" aria-hidden="true"></i>',
					paginate: {
						first: '<i class="fas fa-fast-backward"></i>',
						last: '<i class="fas fa-fast-forward"></i>',
						next: '<i class="fas fa-step-forward"></i>',
						previous: '<i class="fas fa-step-backward"></i>',
					},
				},
				drawCallback: function (settings) {
					// console.log(table_id.indexOf("#"));
					// return;

					var tableInfo = this.api().page.info(); // Use 'this.api()' to ensure access to correct instance

					let totalRecords = tableInfo.recordsTotal || tableInfo.recordsDisplay;
					let selectedLength = this.api().page.len(); // Selected lengthMenu value
					// console.log("Total Records:", totalRecords);

					let lengthLabel = $(table_id)
						.closest(".dataTables_wrapper")
						.find(".dataTables_length label");

					var lengthLablDesc =
						selectedLength > totalRecords
							? "<span> Entries</span>"
							: "<span>out of " + totalRecords + " Entries</span>";

					lengthLabel.find("span").remove(); // Remove any previous appended spans
					lengthLabel.append(lengthLablDesc);
				},
				processing: true,
				serverSide: false,
				order: [],
				select: true,
				lengthMenu: [
					[10, 50, 100, 500, 1000, 5000, 10000],
					[10, 50, 100, 500, 1000, 5000, 10000],
				],
			});
		}

		return tbl;
	}

	function load_normal_datatables($table, table_url = "") {
		if (!$table.length) {
			return;
		}
		$table.DataTable({
			language: {
				emptyTable: "No data available",
				lengthMenu: "Display _MENU_",
				info: "Displaying _START_ to _END_ of _TOTAL_ entries",
				infoEmpty: "Displaying 0 to 0 of 0 entries",
				search: '<i class="fa fa-search" aria-hidden="true"></i>',
				paginate: {
					first: '<i class="fas fa-fast-backward"></i>',
					last: '<i class="fas fa-fast-forward"></i>',
					next: '<i class="fas fa-step-forward"></i>',
					previous: '<i class="fas fa-step-backward"></i>',
				},
			},
			processing: true,
			serverSide: false,
			order: [],
			select: true,
			// scrollX: true,
			// autoWidth: false,
			// fixedColumns: {
			// 	leftColumns: 0, // This disables fixing the first column
			// 	rightColumns: 1, // This fixes only the last column
			// },
			// columnDefs: [
			// 	{
			// 		targets: -1,
			// 		width: "120px",
			// 		className: "text-center",
			// 		orderable: false,
			// 	},
			// ],
			responsive: true,
			columnDefs: [
				{ responsivePriority: 1, targets: 0 },
				{ responsivePriority: 2, targets: -1 },
				{ responsivePriority: 3, targets: 1 },
				{ responsivePriority: 4, targets: -2 },
				{ responsivePriority: 5, targets: 2 },
			],
			lengthMenu: [
				[10, 50, 100, 500, 1000, 5000, 10000],
				[10, 50, 100, 500, 1000, 5000, 10000],
			],
			drawCallback: function (settings) {
				let tableInstance = $table.DataTable();
				let tableInfo = tableInstance.page.info();
				let totalRecords = tableInfo.recordsTotal || tableInfo.recordsDisplay;
				let selectedLength = tableInstance.page.len();

				let lengthLabel = $table
					.closest(".dataTables_wrapper")
					.find(".dataTables_length label");
				let lengthLabelDesc =
					selectedLength > totalRecords
						? "<span> Entries</span>"
						: "<span>out of " + totalRecords + " Entries</span>";
				lengthLabel.find("span").remove();
				lengthLabel.append(lengthLabelDesc);
			},
		});
	}

	if (baseUrl.indexOf("dashboard") !== -1) {
		var param_date = $("#dashboard-filter-calendar").val();
		var new_date = param_date.replace(/\//g, "-");

		let dt_receivables_url = baseUrl + "/dashboard-grid/" + new_date + "/0/";
		let dt_cleared_url = baseUrl + "/dashboard-grid/" + new_date + "/1/";
		let dt_non_credit_url = baseUrl + "/dashboard-grid/" + new_date + "/2/";
		let dt_monthly_all_url =
			baseUrl + "/dashboard-grid-monthly-data/" + new_date;
		let dt_per_trans_url =
			baseUrl + "/dashboard-grid-per-trans/" + new_date + "/1/";
		let dt_per_customer_url =
			baseUrl + "/dashboard-grid-per-trans/" + new_date + "/0/";

		load_dashboard(param_date);

		var tbl_dashboard_non_credit = get_dashboard_datatables(
			"#tbl_dashboard_non_credit",
			dt_non_credit_url
		);

		var tbl_dashboard_receivables = get_dashboard_datatables(
			"#tbl_dashboard_receivables",
			dt_receivables_url
		);

		var tbl_dashboard_cleared = get_dashboard_datatables(
			"#tbl_dashboard_cleared",
			dt_cleared_url
		);

		var tbl_dashboard_monthly_all = get_dashboard_datatables(
			"#tbl_dashboard_monthly_all",
			dt_monthly_all_url
		);

		var tbl_dashboard_transaction_all = get_dashboard_datatables(
			"#tbl_dashboard_transaction_all",
			dt_per_trans_url
		);

		var tbl_dashboard_customer_all = get_dashboard_datatables(
			"#tbl_dashboard_customer_all",
			dt_per_customer_url
		);

		$(document).on("click", ".dl_tbl_dashboard_receivables", function (e) {
			tbl_dashboard_receivables.button(".buttons-excel").trigger();
		});
		$(document).on("click", ".dl_tbl_dashboard_cleared", function (e) {
			tbl_dashboard_cleared.button(".buttons-excel").trigger();
		});
		$(document).on("click", ".dl_tbl_dashboard_non_credit", function (e) {
			tbl_dashboard_non_credit.button(".buttons-excel").trigger();
		});
		$(document).on("click", ".dl_tbl_dashboard_monthly_all", function (e) {
			tbl_dashboard_monthly_all.button(".buttons-excel").trigger();
		});
		$(document).on("click", ".dl_tbl_dashboard_transaction_all", function (e) {
			tbl_dashboard_transaction_all.button(".buttons-excel").trigger();
		});
		$(document).on("click", ".dl_tbl_dashboard_customer_all", function (e) {
			tbl_dashboard_customer_all.button(".buttons-excel").trigger();
		});

		$("#is_active_only_for_transaction_all").change(function () {
			if (this.checked) {
				var is_active = 1;
			} else {
				var is_active = 0;
			}

			tbl_dashboard_transaction_all.ajax
				.url(
					baseUrl + "/dashboard-grid-per-trans/" + new_date + "/1/" + is_active
				)
				.load();
			tbl_dashboard_transaction_all.columns.adjust();
		});

		$("#is_active_only_for_customer_all").change(function () {
			if (this.checked) {
				var is_active = 1;
			} else {
				var is_active = 0;
			}

			tbl_dashboard_customer_all.ajax
				.url(
					baseUrl + "/dashboard-grid-per-trans/" + new_date + "/0/" + is_active
				)
				.load();
			tbl_dashboard_customer_all.columns.adjust();
		});

		$("#is_active_only_for_monthly_all").change(function () {
			if (this.checked) {
				var is_active = 1;
			} else {
				var is_active = 0;
			}

			tbl_dashboard_monthly_all.ajax
				.url(
					baseUrl + "/dashboard-grid-monthly-data/" + new_date + "/" + is_active
				)
				.load();
			tbl_dashboard_monthly_all.columns.adjust();
		});
	}

	function load_dashboard(date) {
		$("#loader-div").removeClass("loaded");

		$.ajax({
			url: baseUrl + "/get-dashboard-data",
			data: { date_range: date },
			method: "POST",
			success: function (response) {
				var parse_response = JSON.parse(response);

				if (parse_response["result"] == 1) {
					var totalVoucherQty = parse_response["total_coupon_qty"];
					var totalVoucherAmount = parse_response["total_coupon_value"];
					var avgPaymentTerms = parse_response["avg_payment_terms"];
					var nearDue = parse_response["near_due_date"];
					var avgCouponValue = parse_response["avg_coupon_value"];
					$("#totalVoucherQty").html(
						number_format(totalVoucherQty, 0, ".", ",")
					);
					$("#totalVoucherAmount").html(
						number_format(totalVoucherAmount, 0, ".", ",")
					);
					$("#avgPaymentTerms").html(
						number_format(avgPaymentTerms, 0, ".", ",") + " DAYS"
					);
					$("#avgCouponValue").html(number_format(avgCouponValue, 2, ".", ","));
					$("#nearDue").html(nearDue);
					$("#loader-div").addClass("loaded");
				} else {
					$("#loader-div").addClass("loaded");
				}

				if (parse_response["paid_result"] == 1) {
					var totalVoucherQtyPaid = parse_response["paid_total_coupon_qty"];
					var totalVoucherAmountPaid =
						parse_response["paid_total_coupon_value"];
					var avgPaymentTermsPaid = parse_response["paid_avg_payment_terms"];
					var avgCouponValuePaid = parse_response["paid_avg_coupon_value"];

					$("#totalVoucherQtyPaid").html(
						number_format(totalVoucherQtyPaid, 0, ".", ",")
					);
					$("#totalVoucherAmountPaid").html(
						number_format(totalVoucherAmountPaid, 0, ".", ",")
					);
					$("#avgPaymentTermsPaid").html(
						number_format(avgPaymentTermsPaid, 0, ".", ",") + " DAYS"
					);
					$("#avgCouponValuePaid").html(
						number_format(avgCouponValuePaid, 2, ".", ",")
					);
					// console.log(number_format(avgCouponValuePaid, 2, ".", ","));
					$("#loader-div").addClass("loaded");
				} else {
					$("#loader-div").addClass("loaded");
				}

				if (parse_response["non_credit_result"] == 1) {
					var totalVoucherQtyNonCredit =
						parse_response["non_credit_total_coupon_qty"];
					var totalVoucherAmountNonCredit =
						parse_response["non_credit_total_coupon_value"];
					var avgCouponValueNonCredit =
						parse_response["non_credit_avg_coupon_value"];

					$("#totalVoucherQtyNonCredit").html(
						number_format(totalVoucherQtyNonCredit, 0, ".", ",")
					);
					$("#totalVoucherAmountNonCredit").html(
						number_format(totalVoucherAmountNonCredit, 0, ".", ",")
					);
					$("#avgCouponValueNonCredit").html(
						number_format(avgCouponValueNonCredit, 2, ".", ",")
					);

					$("#loader-div").addClass("loaded");
				} else {
					$("#loader-div").addClass("loaded");
				}

				let volStatData = {
					labels: parse_response["coupon_status_labels"],
					datasets: [
						{
							label: "Volume of Coupons",
							data: parse_response["coupon_status_qty"],
							backgroundColor: parse_response["coupon_status_background_color"],
							borderColor: parse_response["coupon_status_border_color"],
							borderWidth: 0.8,
						},
					],
				};

				var maxBarThickness = 80;
				let amountStatData = {
					labels: parse_response["coupon_status_labels"],
					datasets: [
						{
							label: "Amount (Peso) of Coupons",
							data: parse_response["coupon_status_value"],
							backgroundColor: parse_response["coupon_status_background_color"],
							borderColor: parse_response["coupon_status_border_color"],
							borderWidth: 0.8,
							barThickness: Math.max(
								40,
								maxBarThickness / parse_response["coupon_status_value"].length
							),
						},
					],
				};

				var maxBarThickness = 80;
				let volPaymentTypeData = {
					labels: parse_response["payment_type_labels"],
					datasets: [
						{
							label: "Volume of Coupons",
							data: parse_response["payment_type_coupon_qty"],
							backgroundColor: [
								"rgba(21, 229, 14, 0.86)",
								"rgba(255, 99, 132, 0.8)",
								"rgba(54, 162, 235, 0.8)",
								"rgba(254, 38, 0, 0.89)",
								"rgba(153, 102, 255, 0.8)",
								"rgba(255, 159, 64, 0.8)",
							],
							borderColor: [
								"rgba(22, 134, 18, 0.86)",
								"rgb(194, 71, 98)",
								"rgb(52, 121, 168)",
								"rgba(220, 53, 24, 0.89)",
								"rgb(95, 60, 165)",
								"rgb(202, 124, 47)",
							],
							borderWidth: 0.5,
							// barThickness: Math.max(
							// 	40,
							// 	maxBarThickness /
							// 		parse_response["payment_type_coupon_qty"].length
							// ),
						},
					],
				};

				let amountPaymentTypeData = {
					labels: parse_response["payment_type_labels"],
					datasets: [
						{
							label: "Volume of Coupons",
							data: parse_response["payment_type_coupon_value"],
							backgroundColor: [
								"rgba(21, 229, 14, 0.86)",
								"rgba(255, 99, 132, 0.8)",
								"rgba(54, 162, 235, 0.8)",
								"rgba(254, 38, 0, 0.89)",
								"rgba(153, 102, 255, 0.8)",
								"rgba(255, 159, 64, 0.8)",
							],
							borderColor: [
								"rgba(22, 134, 18, 0.86)",
								"rgb(194, 71, 98)",
								"rgb(52, 121, 168)",
								"rgba(220, 53, 24, 0.89)",
								"rgb(95, 60, 165)",
								"rgb(202, 124, 47)",
							],
							borderWidth: 0.5,
							barThickness: Math.max(
								40,
								maxBarThickness /
									parse_response["payment_type_coupon_value"].length
							),
						},
					],
				};

				let voucherTrend = {
					labels: parse_response["monthly_name"],
					datasets: [
						{
							label: "ACTIVE",
							data: parse_response["monthly_active_coupon_qty"],
							fill: false,
							borderColor: "rgb(48, 187, 20)",
							backgroundColor: "rgba(238, 252, 230, 0.96)",
							// pointHitRadius: 10,
							// pointBorderWidth: 2,
							tension: 0.3,
							borderWidth: 1.5,
							// order: 1,
						},
						{
							label: "EXPIRED",
							data: parse_response["monthly_expired_coupon_qty"],
							fill: false,
							borderColor: "rgb(56, 14, 14)",
							backgroundColor: "rgba(251, 251, 251, 0.96)",
							tension: 0.3,
							borderWidth: 1.5,
							// order: 3,
						},
						{
							label: "REDEEMED",
							data: parse_response["monthly_redeemed_coupon_qty"],
							fill: false,
							borderColor: "rgb(245, 26, 26)",
							backgroundColor: "rgba(244, 214, 212, 0.96)",
							tension: 0.3,
							borderWidth: 1.5,
							// order: 2,
						},
						{
							label: "INACTIVE",
							data: parse_response["monthly_inactive_coupon_qty"],
							fill: false,
							borderColor: "rgba(255, 194, 81, 0.9)",
							backgroundColor: "rgba(236, 218, 185, 0.9)",
							tension: 0.3,
							borderWidth: 1.5,
							// order: 2,
						},
					],
				};

				create_line_chart("voucherTrend", voucherTrend);

				create_donut_chart(
					"volBasedOnStat",
					volStatData,
					"volBasedOnStatLabel",
					"totalVolBasedOnStat"
				);

				create_pie_chart(
					"volBasedOnPaymentType",
					volPaymentTypeData,
					"volBasedOnPaymentTypeLabel",
					"totalVolBasedOnPaymentType"
				);

				create_bar_chart(
					"amountBasedOnStat",
					amountStatData,
					"amountBasedOnStatLabel",
					"totalAmountBasedOnStat"
				);

				create_bar_chart(
					"amountBasedOnPaymentType",
					amountPaymentTypeData,
					"amountBasedOnPaymentTypeLabel",
					"totalAmountBasedOnPaymentType"
				);
			},
		});

		$("#loader-div").removeClass("loaded");
	}

	function number_format(number, decimals, dec_point, thousands_sep) {
		// *     example: number_format(1234.56, 2, ',', ' ');
		// *     return: '1 234,56'
		number = (number + "").replace(",", "").replace(" ", "");
		var n = !isFinite(+number) ? 0 : +number,
			prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
			sep = typeof thousands_sep === "undefined" ? "," : thousands_sep,
			dec = typeof dec_point === "undefined" ? "." : dec_point,
			s = "",
			toFixedFix = function (n, prec) {
				var k = Math.pow(10, prec);
				return "" + Math.round(n * k) / k;
			};
		// Fix for IE parseFloat(0.55).toFixed(0) = 0;
		s = (prec ? toFixedFix(n, prec) : "" + Math.round(n)).split(".");
		if (s[0].length > 3) {
			s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
		}
		if ((s[1] || "").length < prec) {
			s[1] = s[1] || "";
			s[1] += new Array(prec - s[1].length + 1).join("0");
		}
		return s.join(dec);
	}

	const fillOrderPlugin = {
		id: "fillOrderPlugin",
		beforeInit(chart) {
			// Ensure dataset order is applied before rendering
			chart.data.datasets.forEach((dataset, index) => {
				dataset.order = index + 1; // Assign increasing order (lower fills first)
				console.log(dataset.order);
			});
		},
	};

	function create_line_chart(element_id, chart_data = "") {
		const ctx = document.getElementById(element_id).getContext("2d");
		const myChart = new Chart(ctx, {
			type: "line",
			data: chart_data,
			options: {
				response: true,
				maintainAspectRatio: false,
				scales: {
					// y: [
					// 	{
					// 		beginAtZero: true,
					// 		// time: {
					// 		// 	unit: 'date'
					// 		// },
					// 		grid: {
					// 			display: false,
					// 		},
					// 		ticks: {
					// 			maxTicksLimit: 0,
					// 		},
					// 	},
					// ],
					// x: [
					// 	{
					// 		// time: {
					// 		// 	unit: 'date'
					// 		// },
					// 		grid: {
					// 			display: false,
					// 		},
					// 		ticks: {
					// 			maxTicksLimit: 0,
					// 		},
					// 	},
					// ],
				},
				layout: {
					padding: {
						left: 10,
						right: 10,
						top: 10,
						bottom: 0,
					},
				},
				plugins: {
					tooltip: {
						mode: "index", // Ensures tooltip shows values from all datasets
						intersect: false,
						callbacks: {
							title: function (tooltipItems) {
								return tooltipItems[0].label; // Show the x-axis label
							},
							label: function (tooltipItem) {
								return `${tooltipItem.dataset.label}: ${tooltipItem.raw}`; // Show dataset label + value
							},
							footer: function (tooltipItems) {
								// Compute and show the sum of all values at this point
								let total = tooltipItems.reduce(
									(sum, item) => sum + item.raw,
									0
								);
								return `Total: ${total}`;
							},
						},
					},
				},
			},
			// plugins: [fillOrderPlugin],
		});
	}

	function create_donut_chart(
		element_id,
		chart_data = "",
		label_id = "",
		total_id = ""
	) {
		const ctxDonut2 = document.getElementById(element_id).getContext("2d");

		const myChartDonut2 = new Chart(ctxDonut2, {
			type: "doughnut",
			data: chart_data,
			options: {
				cutout: "55%", // Adjust the cutout percentage for donut thickness.
				responsive: true,
				maintainAspectRatio: false,
				radius: "100%",
			},
		});

		create_chart_label(chart_data, label_id, total_id);
	}

	function create_pie_chart(
		element_id,
		chart_data = "",
		label_id = "",
		total_id = ""
	) {
		const ctxDonut2 = document.getElementById(element_id).getContext("2d");

		const myChartDonut2 = new Chart(ctxDonut2, {
			type: "pie",
			data: chart_data,
			options: {
				responsive: true,
				maintainAspectRatio: false,
				radius: "100%",
			},
		});

		create_chart_label(chart_data, label_id, total_id);
	}

	function create_bar_chart(
		element_id,
		chart_data = "",
		label_id = "",
		total_id = ""
	) {
		const ctxDonut2 = document.getElementById(element_id).getContext("2d");

		const myChartDonut2 = new Chart(ctxDonut2, {
			type: "bar",
			data: chart_data,
			options: {
				responsive: true,
				maintainAspectRatio: true,
				scales: {
					y: {
						beginAtZero: true,
					},
					x: {
						barPercentage: 0.6, // Adjust bar width relative to category width
						categoryPercentage: 0.8, // Adjusts how much space bars take in a category
						ticks: {
							callback: function (value, index) {
								let label = chart_data.labels[index]; // Get label from our array
								// console.log(label);
								return label.length > 7 ? label.substring(0, 7) + "..." : label;
							},
						},
					},
				},
				plugins: {
					legend: {
						display: false, // 🚀 Hides the legend
					},
				},
				// radius: "100%",
			},
		});

		create_chart_label(chart_data, label_id, total_id);
	}

	function create_horizontal_bar_chart(
		element_id,
		chart_data = "",
		label_id = "",
		total_id = ""
	) {
		const ctxDonut2 = document.getElementById(element_id).getContext("2d");

		const myChartDonut2 = new Chart(ctxDonut2, {
			type: "bar",
			data: chart_data,
			options: {
				indexAxis: "y", // 🔥 Makes it a horizontal bar chart
				responsive: true,
				maintainAspectRatio: true,
				scales: {
					x: {
						beginAtZero: true,
					},
					y: {
						barPercentage: 0.6, // Adjust bar width relative to category width
						categoryPercentage: 0.8, // Adjusts how much space bars take in a category

						ticks: {
							callback: function (value, index) {
								if (chart_data.labels && chart_data.labels[index]) {
									let label = chart_data.labels[index]; // Get label from chart data
									return label.length > 9
										? label.substring(0, 9) + "..."
										: label;
								}
								return value; // Default if label is missing
							},
						},
					},
				},
				plugins: {
					legend: {
						display: false, // 🚀 Hides the legend
					},
				},
				// radius: "100%",
			},
		});

		create_chart_label(chart_data, label_id, total_id);
	}

	function create_chart_label(chart_data = "", label_id = "", total_id = "") {
		const chartLabels = document.getElementById(label_id);

		chart_data.labels.forEach((label, index) => {
			const listItem = document.createElement("li");
			let value = chart_data.datasets[0].data[index];
			let total = chart_data.datasets[0].data.reduce((a, b) => a + b, 0);
			// let percentage = ((value / total) * 100).toFixed(2) + "%";
			let percentage = ((value / total) * 100).toFixed(2);

			listItem.textContent = `${label}: ${number_format(
				value
			)} (${percentage}%)`;
			listItem.style.color = chart_data.datasets[0].borderColor[index];
			// listItem.addClass = "list-group-item";
			listItem.classList.add("list-group-item");
			// listItem.style.fontWeight = "bold";
			chartLabels.appendChild(listItem);

			if (total_id) {
				const myDiv = document.getElementById(total_id);
				myDiv.innerHTML =
					"TOTAL : <strong>" + number_format(total) + "</strong>";
			}
		});

		// console.log("#" + total_id);
		// total_el = "#" + total_id;
		// $(total_id).text("hello");
	}

	function create_tooltip(context, chart_data) {
		const tooltip = context.tooltip;
		if (tooltip && tooltip.opacity !== 0) {
			const index = tooltip.dataPoints[0].dataIndex;
			const label = chart_data.labels[index];
			const value = chart_data.datasets[0].data[index];
			const total = chart_data.datasets[0].data.reduce((a, b) => a + b, 0);
			const percentage = ((value / total) * 100).toFixed(2) + "%";

			infoTitle.textContent = label;
			infoValue.textContent = "Value: " + value;
			infoPercentage.textContent = "Percentage: " + percentage;
		} else {
			// Clear info when tooltip is hidden
			infoTitle.textContent = "";
			infoValue.textContent = "";
			infoPercentage.textContent = "";
		}

		console.log(tooltip);
	}
});
