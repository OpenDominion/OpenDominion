<div class="row">
    <div class="col-md-12">
        <table class="table">
            <colgroup>
                <col width="25%">
                <col width="25%">
                <col width="25%">
                <col width="25%">
            </colgroup>
            <thead>
                <tr>
                    <th>Operation</th>
                    <th>Cost (Strength)</th>
                    <th>Points Awarded</th>
                    <th>
                        <small class="text-muted">
                            You have {{ sprintf("%.4g", $selectedDominion->spy_strength) }}% spy strength.
                        </small>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Small Party</td>
                    <td>15%</td>
                    <td>15 points</td>
                    <td>
                        <button type="submit" name="action" value="espionage[0]" class="btn btn-block btn-sm btn-primary">
                            Issue Orders
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>Medium Party</td>
                    <td>30%</td>
                    <td>35 points</td>
                    <td>
                        <button type="submit" name="action" value="espionage[0]" class="btn btn-block btn-sm btn-primary">
                            Issue Orders
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>Large Party</td>
                    <td>60%</td>
                    <td>75 points</td>
                    <td>
                        <button type="submit" name="action" value="espionage[2]" class="btn btn-block btn-sm btn-primary">
                            Issue Orders
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
