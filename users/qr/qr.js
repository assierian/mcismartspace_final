document.addEventListener('DOMContentLoaded', function () {
  // Global variable to store user's department
  let cachedUserDepartment = '';
  
  // Function to get the user's department
  function getUserDepartment() {
    return cachedUserDepartment;
  }
  
  // Fetch the user's department from the server
  function fetchUserDepartment() {
    return fetch('api/get_user_department.php')
      .then(response => response.json())
      .then(data => {
        if (data.success && data.department) {
          cachedUserDepartment = data.department;
          console.log('User department loaded:', cachedUserDepartment);
        } else {
          console.warn('Could not load user department');
        }
        return data;
      })
      .catch(error => {
        console.error('Error fetching user department:', error);
        return { success: false, department: '' };
      });
  }
  
  // Fetch user department when page loads
  fetchUserDepartment();

  // Tab switching
  const tabs = document.querySelectorAll('.option-tab');
  const contents = document.querySelectorAll('.option-content');

  // Add validation styles
  const styleElement = document.createElement('style');
  styleElement.textContent = `
                .input-wrapper {
                    position: relative;
                }
                .validation-feedback {
                    font-size: 0.85rem;
                    margin-top: 0.3rem;
                    transition: all 0.3s ease;
                    height: 20px;
                }
                .validation-error {
                    color: #e11d48;
                }
                .validation-success {
                    color: #0f9d58;
                }
                .form-control.is-invalid {
                    border-color: #e11d48;
                }
                .form-control.is-valid {
                    border-color: #0f9d58;
                }
                .validation-spinner {
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    border: 2px solid rgba(15, 66, 40, 0.1);
                    border-top: 2px solid #0f4228;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin-right: 8px;
                    vertical-align: middle;
                }
                #equipment-room:disabled, #equipment-building:disabled {
                    background-color: #f8f9fa;
                    cursor: not-allowed;
                }
            `;
  document.head.appendChild(styleElement);

  tabs.forEach((tab) => {
    tab.addEventListener('click', function () {
      // Remove active class from all tabs and contents
      tabs.forEach((t) => t.classList.remove('active'));
      contents.forEach((c) => c.classList.remove('active'));

      // Add active class to current tab and content
      this.classList.add('active');
      const tabName = this.getAttribute('data-tab');
      document.getElementById(`${tabName}-content`).classList.add('active');

      // If we're switching to camera tab, show camera preview
      if (tabName === 'scan' && !scanning) {
        showCameraPreview();
      }

      // If we're switching away from camera tab, stop scanning
      if (tabName !== 'scan' && scanning) {
        stopScanning();
      } else if (tabName !== 'scan' && cameraActive) {
        stopCameraPreview();
      }
    });
  });

  // Camera scanning functionality using ZXing-JS
  const startButton = document.getElementById('start-button');
  const qrVideo = document.getElementById('qr-video');
  const cameraPlaceholder = document.getElementById('camera-placeholder');
  const scanningEffect = document.getElementById('scanning-effect');

  let codeReader = null;
  let scanning = false;
  let cameraActive = false;
  let videoStream = null;

  // Initialize ZXing code reader
  function initializeZXingReader() {
    if (!codeReader) {
      codeReader = new ZXing.BrowserQRCodeReader();
    }
    return codeReader;
  }

  // Initialize camera preview when page loads
  showCameraPreview();

  startButton.addEventListener('click', function () {
    if (!scanning) {
      startScanning();
    } else {
      stopScanning();
    }
  });

  // Function to show camera preview without scanning
  async function showCameraPreview() {
    if (cameraActive) return;

    try {
      // Hide placeholder and show video
      cameraPlaceholder.style.display = 'none';
      qrVideo.style.display = 'block';

      // Show loading indicator until camera starts
      document.getElementById('scan-content').classList.add('loading');

      // Get camera stream
      videoStream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: 'environment',
        },
      });

      // Attach stream to video element
      qrVideo.srcObject = videoStream;
      qrVideo.play();

      // Remove loading indicator
      document.getElementById('scan-content').classList.remove('loading');

      cameraActive = true;
      startButton.textContent = 'Start Scanning';
    } catch (error) {
      console.error('Error starting camera preview:', error);
      cameraPlaceholder.style.display = 'flex';
      qrVideo.style.display = 'none';
      document.getElementById('scan-content').classList.remove('loading');
      alert(
        'Could not access camera. Please ensure you have granted camera permissions or try the upload option instead.'
      );
    }
  }

  // Function to stop camera preview
  function stopCameraPreview() {
    if (!cameraActive) return;

    // Stop video stream
    if (videoStream) {
      videoStream.getTracks().forEach((track) => track.stop());
      videoStream = null;
    }

    // Reset UI
    cameraPlaceholder.style.display = 'flex';
    qrVideo.style.display = 'none';
    qrVideo.srcObject = null;
    cameraActive = false;
  }

  // Function to start scanning using ZXing-JS
  async function startScanning() {
    if (!cameraActive) {
      await showCameraPreview();
    }

    try {
      // Initialize ZXing reader
      const reader = initializeZXingReader();
      
      // Update button text and start scanning effect
      startButton.innerHTML = '<i class="fa fa-stop"></i> Stop Scanning';
      scanningEffect.style.display = 'block';
      scanning = true;

      // Start continuous scanning
      reader.decodeFromVideoDevice(null, qrVideo, (result, error) => {
        if (result) {
          // QR code detected
          console.log('ZXing detected QR code:', result.text);
          stopScanning();
          handleSuccessfulScan(result.text);
        }
        // Ignore errors during scanning (they're expected when no QR code is visible)
      });

    } catch (error) {
      console.error('Error starting ZXing scanner:', error);
      alert('Could not start QR code scanner. Please try the upload option instead.');
      stopScanning();
      
      // Switch to upload tab if camera fails
      document.querySelector('.option-tab[data-tab="upload"]').click();
    }
  }

  // Function to stop scanning
  function stopScanning() {
    if (codeReader && scanning) {
      codeReader.reset();
    }
    
    // Update UI
    scanningEffect.style.display = 'none';
    startButton.textContent = 'Start Scanning';
    scanning = false;
  }

  // Image upload functionality
  const fileInput = document.getElementById('file-input');
  const uploadArea = document.getElementById('upload-area');
  const previewImage = document.getElementById('preview-image');
  const processImageButton = document.getElementById('process-image-button');
  const imagePreviewContainer = document.getElementById(
    'image-preview-container'
  );

  uploadArea.addEventListener('click', function () {
    fileInput.click();
  });

  // Drag and drop functionality
  uploadArea.addEventListener('dragover', function (e) {
    e.preventDefault();
    uploadArea.style.borderColor = '#10b981';
    uploadArea.style.backgroundColor = '#f8fafc';
  });

  uploadArea.addEventListener('dragleave', function () {
    uploadArea.style.borderColor = '#ccc';
    uploadArea.style.backgroundColor = '';
  });

  uploadArea.addEventListener('drop', function (e) {
    e.preventDefault();
    uploadArea.style.borderColor = '#ccc';
    uploadArea.style.backgroundColor = '';

    if (e.dataTransfer.files.length) {
      handleFile(e.dataTransfer.files[0]);
    }
  });

  fileInput.addEventListener('change', function () {
    if (this.files.length) {
      handleFile(this.files[0]);
    }
  });

  function handleFile(file) {
    if (!file.type.match('image.*')) {
      alert('Please select an image file');
      return;
    }

    // Display preview
    const reader = new FileReader();
    reader.onload = function (e) {
      previewImage.src = e.target.result;
      previewImage.style.display = 'block';
      processImageButton.disabled = false;
      processImageButton.innerHTML = 'Process QR Code';
    };
    reader.readAsDataURL(file);
  }

  // Process QR Code button event listener
  processImageButton.addEventListener('click', function () {
    // Show loading state
    processImageButton.disabled = true;
    processImageButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing QR code...';

    // Process the image with ZXing-JS
    processImageWithZXing(previewImage.src);
  });

  // Function to process uploaded image with ZXing-JS
  async function processImageWithZXing(imageSrc) {
    try {
      // Initialize ZXing reader
      const reader = initializeZXingReader();
      
      // Create image element for processing
      const img = new Image();
      img.crossOrigin = 'Anonymous';
      
      img.onload = async function() {
        try {
          // Decode QR code from image
          const result = await reader.decodeFromImageElement(img);
          console.log('ZXing decoded from image:', result.text);
          handleSuccessfulScan(result.text);
        } catch (error) {
          console.error('ZXing image decode error:', error);
          showScanError('Could not detect a valid QR code in this image. Please ensure the QR code is clearly visible, well-lit, and not blurry.');
        }
      };
      
      img.onerror = function() {
        console.error('Image loading error');
        showScanError('Failed to load the image. Please check the image format and try again.');
      };
      
      img.src = imageSrc;
      
    } catch (error) {
      console.error('Error initializing ZXing for image processing:', error);
      showScanError('QR code scanner initialization failed. Please try again.');
    }
  }

  // Manual entry form elements
  const buildingSelect = document.getElementById('building-select');
  const roomSelect = document.getElementById('room-select');
  const equipmentUnitSelect = document.getElementById('equipment-unit-select');
  const equipmentIdInput = document.getElementById('equipment-id');
  const manualSubmitButton = document.getElementById('manual-submit-button');

  // Hidden fields
  const selectedUnitId = document.getElementById('selected-unit-id');
  const selectedRoomName = document.getElementById('selected-room-name');
  const selectedBuildingName = document.getElementById(
    'selected-building-name'
  );

  // Load initial data
  loadBuildingOptions();

  buildingSelect.addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    // Reset subsequent dropdowns
    roomSelect.innerHTML =
      '<option value="">-- Select Building First --</option>';
    roomSelect.disabled = true;
    equipmentUnitSelect.innerHTML =
      '<option value="">-- Select Room First --</option>';
    equipmentUnitSelect.disabled = true;
    selectedUnitId.value = '';
    selectedRoomName.value = '';
    selectedBuildingName.value = '';
    manualSubmitButton.disabled = true;

    if (this.value) {
      selectedBuildingName.value = selectedOption.textContent;
      loadRoomOptions(this.value);
      roomSelect.disabled = false;
    }
  });

  roomSelect.addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    // Reset subsequent dropdowns
    equipmentUnitSelect.innerHTML =
      '<option value="">-- Select Room First --</option>';
    equipmentUnitSelect.disabled = true;
    selectedUnitId.value = '';
    selectedRoomName.value = '';
    manualSubmitButton.disabled = true;

    if (this.value) {
      selectedRoomName.value = selectedOption.textContent;
      loadEquipmentUnitOptions(this.value);
      equipmentUnitSelect.disabled = false;
    }
  });

  equipmentUnitSelect.addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    if (this.value) {
      selectedUnitId.value = this.value;
    } else {
      selectedUnitId.value = '';
    }
    checkFormValidity();
  });

  // Load building options
  function loadBuildingOptions() {
    // Show loading indicator
    buildingSelect.innerHTML = '<option value="">Loading buildings...</option>';
    
    // First ensure we have the user's department, then load buildings
    fetchUserDepartment()
      .then(() => fetch('api/get_equipment_data.php?action=buildings'))
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          buildingSelect.innerHTML =
            '<option value="">-- Select Building --</option>';

          // Get user's department if available
          let userDepartment = '';
          if (typeof getUserDepartment === 'function') {
            userDepartment = getUserDepartment();
          }

          data.data.forEach((building) => {
            const option = document.createElement('option');
            option.value = building.id;
            
            // Add visual indicators for department buildings vs common buildings
            const department = building.department;
            const isCommon = department === 'Common' || building.building_name.includes('Sports');
            
            // Format option text with department tag
            if (isCommon) {
              // For common buildings
              option.textContent = `${building.building_name} [Common]`;
              option.classList.add('common-building');
            } else if (department === userDepartment) {
              // For department-specific buildings
              option.textContent = `${building.building_name} [Your Department]`;
              option.classList.add('your-department');
            } else {
              // For other department buildings (should not appear in filtered list)
              option.textContent = `${building.building_name} [${department}]`;
            }
            
            option.setAttribute('data-department', department);
            buildingSelect.appendChild(option);
          });

          // Add custom styles to differentiate building types
          const styleEl = document.createElement('style');
          styleEl.textContent = `
            .common-building {
              color: #1B4332;
              font-weight: normal;
            }
            .your-department {
              color: #0F4C3A;
              font-weight: bold;
            }
          `;
          document.head.appendChild(styleEl);
          
        } else {
          showError('Failed to load building options');
        }
      })
      .catch((error) => {
        console.error('Error loading buildings:', error);
        showError('Error loading building options');
      });
  }

  // Load room options based on selected building
  function loadRoomOptions(buildingId) {
    roomSelect.innerHTML = '<option value="">Loading rooms...</option>';

    fetch(`api/get_equipment_data.php?action=rooms&building_id=${buildingId}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success && data.data.length > 0) {
          roomSelect.innerHTML = '<option value="">-- Select Room --</option>';

          data.data.forEach((room) => {
            const option = document.createElement('option');
            option.value = room.id;
            option.textContent = `${room.room_name} (${room.room_type}, Capacity: ${room.capacity})`;
            roomSelect.appendChild(option);
          });
        } else {
          roomSelect.innerHTML = '<option value="">No rooms available</option>';
          if (!data.success) showError('Failed to load room options');
        }
      })
      .catch((error) => {
        roomSelect.innerHTML = '<option value="">Error loading rooms</option>';
        console.error('Error loading rooms:', error);
        showError('Error loading room options');
      });
  }

  // Load equipment unit options based on selected room
  function loadEquipmentUnitOptions(roomId) {
    equipmentUnitSelect.innerHTML =
      '<option value="">Loading units...</option>';

    fetch(`api/get_equipment_units_by_room.php?room_id=${roomId}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success && data.units.length > 0) {
          equipmentUnitSelect.innerHTML =
            '<option value="">-- Select Equipment Unit --</option>';
          data.units.forEach((unit) => {
            const option = document.createElement('option');
            option.value = unit.unit_id;
            option.textContent = `${unit.equipment_name} (SN: ${unit.serial_number})`;
            equipmentUnitSelect.appendChild(option);
          });
        } else {
          equipmentUnitSelect.innerHTML =
            '<option value="">No equipment units in this room</option>';
          if (!data.success) showError('Failed to load equipment units.');
        }
      })
      .catch((error) => {
        equipmentUnitSelect.innerHTML =
          '<option value="">Error loading units</option>';
        console.error('Error loading equipment units:', error);
        showError('Error loading equipment units.');
      });
  }

  // Check if form is valid and enable/disable submit button
  function checkFormValidity() {
    const buildingSelected = buildingSelect.value && selectedBuildingName.value;
    const roomSelected = roomSelect.value && selectedRoomName.value;
    const unitSelected = equipmentUnitSelect.value && selectedUnitId.value;

    if (buildingSelected && roomSelected && unitSelected) {
      manualSubmitButton.disabled = false;
    } else {
      manualSubmitButton.disabled = true;
    }
  }

  // Show success message
  function showSuccess(message) {
    console.log('Success:', message);
  }

  // Show error message
  function showError(message) {
    console.error('Error:', message);
  }

  // Form submission
  manualSubmitButton.addEventListener('click', function () {
    if (manualSubmitButton.disabled) {
      alert('Please select a building, room, and equipment unit.');
      return;
    }

    const selectedUnitOption =
      equipmentUnitSelect.options[equipmentUnitSelect.selectedIndex];

    // Store the manually entered equipment data
    const equipmentData = {
      unit_id: selectedUnitId.value,
      // Pass text content for display on the next page
      name: selectedUnitOption.textContent,
      room: selectedRoomName.value,
      building: selectedBuildingName.value,
      // Keep this for the optional manual ID entry
      user_equipment_id: (equipmentIdInput && equipmentIdInput.value) || null,
      source: 'manual_entry',
    };

    // Save to sessionStorage and redirect
    sessionStorage.setItem('scannedEquipment', JSON.stringify(equipmentData));
    window.location.href = 'report-equipment-issue.php';
  });

  // Function to handle successful scan (reuse existing logic)
  function handleSuccessfulScan(decodedText) {
    console.log('QR Code detected:', decodedText);

    // Remove any existing alerts
    const existingAlerts = document.querySelectorAll(
      '.qr-success-alert, .qr-debug-alert'
    );
    existingAlerts.forEach((alert) => alert.remove());

    // Check if the decoded text is a URL
    const isUrl =
      decodedText.startsWith('http://') || decodedText.startsWith('https://');

    if (isUrl) {
      // Additional validation for our specific URLs
      const isValidEquipmentUrl =
        decodedText.includes('redirect-equipment-report.php') ||
        decodedText.includes('report-equipment-issue.php');

      // Normalize the URL to handle different domain scenarios
      let redirectUrl = decodedText;

      // If the URL is from another domain but contains our paths, adapt it to current domain
      if (
        isValidEquipmentUrl &&
        !decodedText.startsWith(window.location.origin)
      ) {
        try {
          const parsedUrl = new URL(decodedText);
          const pathWithParams = parsedUrl.pathname + parsedUrl.search;

          // Extract only the part after the domain
          let relativePath = pathWithParams;
          // Handle case where path doesn't include /mcmod41
          if (!relativePath.includes('/mcmod41/')) {
            // Try to extract from the last occurrence of /users/
            const usersIndex = relativePath.lastIndexOf('/users/');
            if (usersIndex >= 0) {
              relativePath = '/mcmod41' + relativePath.substring(usersIndex);
            }
          }

          // Create new URL with current domain
          redirectUrl = window.location.origin + relativePath;
          console.log('Normalized URL:', redirectUrl);
        } catch (e) {
          console.error('Error normalizing URL:', e);
        }
      }

      // Show success indicator for URL
      const successAlert = document.createElement('div');
      successAlert.className = 'qr-success-alert';
      successAlert.style.cssText = `
        background: #10b981; 
        color: white; 
        padding: 15px; 
        border-radius: 8px; 
        margin: 10px 0; 
        text-align: center;
        animation: fadeIn 0.3s ease;
      `;
      successAlert.innerHTML =
        '<i class="fa fa-check-circle"></i> QR Code detected! Redirecting...';
      document.querySelector('.qr-scan-container').appendChild(successAlert);

      // Navigate to the URL after a brief delay
      setTimeout(() => {
        console.log('Redirecting to:', redirectUrl); // Use the normalized URL
        window.location.href = redirectUrl; // Use the normalized URL
      }, 1500);
    } else {
      // Show the text content for debugging (non-URL content)
      const debugAlert = document.createElement('div');
      debugAlert.className = 'qr-debug-alert';
      debugAlert.innerHTML = `
        <div style="background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 15px; margin: 10px 0;">
          <h4 style="color: #0369a1; margin: 0 0 10px 0;">
            <i class="fa fa-info-circle"></i> QR Code Content Detected
          </h4>
          <p style="margin: 0; font-family: monospace; background: white; padding: 8px; border-radius: 4px; word-break: break-all;">
            ${decodedText}
          </p>
          <p style="margin: 10px 0 0 0; font-size: 0.9em; color: #64748b;">
            This appears to be text content rather than a URL. If this should redirect to an equipment report, 
            please check that the QR code was generated correctly.
          </p>
        </div>
      `;
      document.querySelector('.qr-scan-container').appendChild(debugAlert);
    }
  }

  // Function to show scan error
  function showScanError(message) {
    alert(message);
    processImageButton.innerHTML = 'Process QR Code';
    processImageButton.disabled = false;
  }
});

// Global functions for modal (outside DOMContentLoaded)
function closeValidationModal() {
  const modal = document.getElementById('equipment-validation-modal');
  modal.style.display = 'none';
}
