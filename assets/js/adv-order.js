$(document).ready(function () {
	$("#modal-add-product-coupon").on("show.bs.modal", function () {
		$("#for-usual-order-details").hide();
		$("#for-advance-order-details").hide();
		// Optionally reset radio buttons if needed
		$(
			"#for-normal-order, #for-advance-order, #for-issue-on-advance-order"
		).prop("checked", false);
	});

	// Show/hide details on radio button click
	$("#for-normal-order").on("change", function () {
		if ($(this).is(":checked")) {
			$("#for-usual-order-details").show();
			$("#for-advance-order-details").hide();

			$(".parent-transaction").hide();
			$("[name='parent_transaction_header_id']").removeAttr("required");

			// Enable and restore required for usual order inputs
			$("#for-usual-order-details :input").each(function () {
				$(this).prop("disabled", false);
				if ($(this).data("was-required")) {
					$(this).attr("required", true);
				}
			});
			// Disable and remove required for advance order inputs
			$("#for-advance-order-details :input").each(function () {
				$(this).prop("disabled", true);
				if ($(this).attr("required")) {
					$(this).data("was-required", true);
				}
				$(this).removeAttr("required");
			});

			$(".for-usual-trans-inputs").show();
			// Enable and restore required for usual order inputs
			$(".for-usual-trans-inputs :input").each(function () {
				$(this).prop("disabled", false);
				if ($(this).data("was-required")) {
					$(this).attr("required", true);
				}
			});
		}
	});
	// Show/hide details on radio button click
	$("#for-issue-on-advance-order").on("change", function () {
		if ($(this).is(":checked")) {
			$("#for-usual-order-details").show();
			$("#for-advance-order-details").hide();

			$(".parent-transaction").show();
			$("[name='parent_transaction_header_id']").attr("required");

			// Enable and restore required for usual order inputs
			$("#for-usual-order-details :input").each(function () {
				$(this).prop("disabled", false);
				if ($(this).data("was-required")) {
					$(this).attr("required", true);
				}
			});
			// Disable and remove required for advance order inputs
			$("#for-advance-order-details :input").each(function () {
				$(this).prop("disabled", true);
				if ($(this).attr("required")) {
					$(this).data("was-required", true);
				}
				$(this).removeAttr("required");
			});

			$(".for-usual-trans-inputs").hide();
			// Disable and remove required for normal order inputs
			$(".for-usual-trans-inputs :input").each(function () {
				$(this).prop("disabled", true);
				if ($(this).attr("required")) {
					$(this).data("was-required", true);
				}
				$(this).removeAttr("required");
			});
		}
	});
	$("#for-advance-order").on("change", function () {
		if ($(this).is(":checked")) {
			$("#for-usual-order-details").hide();
			$("#for-advance-order-details").show();
			// Enable and restore required for advance order inputs
			$("#for-advance-order-details :input").each(function () {
				$(this).prop("disabled", false);
				if ($(this).data("was-required")) {
					$(this).attr("required", true);
				}
			});
			// Disable and remove required for usual order inputs
			$("#for-usual-order-details :input").each(function () {
				$(this).prop("disabled", true);
				if ($(this).attr("required")) {
					$(this).data("was-required", true);
				}
				$(this).removeAttr("required");
			});
		}
	});

	$("[name='parent_transaction_header_id']").on("change", function () {
		var selectedOption = $(this).find("option:selected");
		var stock = parseInt(selectedOption.data("stock"), 10);
		var $qtyInput = $("[name='product_coupon_qty']");
		if (!isNaN(stock) && stock > 0) {
			$qtyInput.attr("max", stock);
			var qtyVal = $qtyInput.val();
			var qty = qtyVal === "" ? 0 : parseInt(qtyVal, 10);
			if (qty > stock) {
				$qtyInput.val(stock);
			}
			// Add change event to enforce max value
			$qtyInput.off("change.maxCheck").on("change.maxCheck", function () {
				var val = $(this).val();
				var num = val === "" ? 0 : parseInt(val, 10);
				if (num > stock) {
					$(this).val(stock);
				}
			});
		} else {
			$qtyInput.removeAttr("max");
			$qtyInput.off("change.maxCheck");
		}
	});
});
