document.addEventListener("DOMContentLoaded",function(){document.getElementById("dropzone-files")&&(new Dropzone("#dropzone-files",{url:"/dashboard/articles/upload-file",paramName:"file",maxFilesize:10,addRemoveLinks:!0,dictDefaultMessage:document.querySelector("#dropzone-files .dz-message").innerHTML,acceptedFiles:".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt",headers:{"X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').content},init:function(){this.on("sending",function(i,t,n){const d=document.getElementById("file_category").value;if(!d){this.removeFile(i),r("Please select a file category before uploading");return}n.append("file_category",d),n.append("article_id",document.querySelector("form").dataset.articleId),n.append("class_id",document.getElementById("class_id").value),n.append("subject_id",document.getElementById("subject_id").value),n.append("semester_id",document.getElementById("semester_id").value),n.append("country",new URLSearchParams(window.location.search).get("country")||"1")}),this.on("success",function(i,t){const n=document.createElement("tr");n.dataset.fileId=t.file.id,n.innerHTML=`
            <td>
              <i class="ti ti-file me-2"></i>
              ${t.file.name}
            </td>
            <td>
              <span class="badge bg-label-${u(t.file.category)}">
                ${t.file.category}
              </span>
            </td>
            <td>${f(t.file.size)}</td>
            <td>
              <div class="d-flex gap-2">
                <a href="${t.file.path}" 
                   class="btn btn-sm btn-label-primary"
                   target="_blank"
                   title="${window.translations.Download||"Download"}">
                  <i class="ti ti-download"></i>
                </a>
                <button type="button" 
                        class="btn btn-sm btn-label-danger delete-file" 
                        data-file-id="${t.file.id}"
                        title="${window.translations.Delete||"Delete"}">
                  <i class="ti ti-trash"></i>
                </button>
              </div>
            </td>
          `,document.getElementById("files-list").appendChild(n),this.removeFile(i)}),this.on("error",function(i,t){r(typeof t=="string"?t:t.error),this.removeFile(i)})}}),document.addEventListener("click",function(i){if(i.target.closest(".delete-file")){const n=i.target.closest(".delete-file").dataset.fileId,d=document.querySelector(`tr[data-file-id="${n}"]`);confirm(window.translations.DeleteConfirm||"Are you sure you want to delete this file?")&&fetch("/dashboard/articles/remove-file",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({file_id:n})}).then(l=>l.json()).then(l=>{l.success?d.remove():r(l.message||window.translations.DeleteError||"Failed to delete file")}).catch(l=>{console.error("Error:",l),r(window.translations.DeleteError||"Failed to delete file")})}}));const e=document.getElementById("meta_description");e&&(e.addEventListener("input",function(){const i=this.getAttribute("maxlength"),t=this.value.length,n=i-t;document.getElementById("meta_chars").textContent=n}),e.dispatchEvent(new Event("input")));const o=document.getElementById("use_title_for_meta"),s=document.getElementById("use_keywords_for_meta"),a=document.getElementById("title"),c=document.getElementById("keywords");o&&s&&e&&(o.addEventListener("change",function(){this.checked&&(s.checked=!1,e.value=a.value,e.dispatchEvent(new Event("input")))}),s.addEventListener("change",function(){this.checked&&(o.checked=!1,e.value=c.value,e.dispatchEvent(new Event("input")))}))});function r(e){Swal.fire({title:window.translations.Error||"Error",text:e,icon:"error",customClass:{confirmButton:"btn btn-primary"},buttonsStyling:!1})}function u(e){return{plans:"primary",papers:"info",tests:"warning",books:"success"}[e]||"primary"}function f(e){if(e===0)return"0 Bytes";const o=1024,s=["Bytes","KB","MB","GB"],a=Math.floor(Math.log(e)/Math.log(o));return parseFloat((e/Math.pow(o,a)).toFixed(2))+" "+s[a]}
