<div class="card">
                    <header class="card-header">
                        <div class="new-title-container">
                            <p class="new-title">Add Building</p>
                        </div>
                    </header>
                    <div class="card-content">
                        <form id="buildingForm" method="POST">
                            <div class="field is-inline">
                                <div class="control">
                                    <label class="label">Building Name:</label>
                                    <input class="input" type="text" name="building_name" required>
                                </div>
                                <div class="control">
                                    <label class="label">Number Of Floors (Max 7):</label>
                                    <input class="input" type="number" name="number_of_floors" min="1" max="7" required>
                                </div>
                            </div>

                            <div class="field is-inline">
                                <div class="control">
                                    <label class="label">Department:</label>
                                    <div class="select">
                                        <select name="department" required>
                                            <option value="">Select Department</option>
                                            <?php
                                            $departments = ['Accountancy', 'Business Administration', 'Hospitality Management', 'Education and Arts', 'Criminal Justice', 'Common'];
                                            foreach ($departments as $dept) {
                                                echo '<option value="' . htmlspecialchars($dept) . '">' . htmlspecialchars($dept) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="field is-inline">
                                <div class="control">
                                    <button type="submit" name="add_building" id="addBuildingBtn" class="styled-button" value="true">Add Building</button>
                                </div>
                            </div>
                            <!-- Hidden field to ensure the add_building value is always included in the AJAX request -->
                            <input type="hidden" name="add_building" value="true">
                        </form>
                    </div>
                </div>