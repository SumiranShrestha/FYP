<script>
$(document).ready(function () {
    $("input[name='query']").keyup(function () {
        let query = $(this).val();
        if (query.length > 2) { // Only search if more than 2 characters
            $.get("server/search_suggestions.php", { query: query }, function (data) {
                $("#searchSuggestions").html(data).show();
            });
        } else {
            $("#searchSuggestions").hide();
        }
    });

    // Hide suggestions when clicking outside
    $(document).on("click", function (event) {
        if (!$(event.target).closest("#searchSuggestions, input[name='query']").length) {
            $("#searchSuggestions").hide();
        }
    });
});
</script>

<!-- Search Suggestions Dropdown -->
<div id="searchSuggestions" class="list-group position-absolute bg-white shadow" style="width: 25%; display: none;"></div>
