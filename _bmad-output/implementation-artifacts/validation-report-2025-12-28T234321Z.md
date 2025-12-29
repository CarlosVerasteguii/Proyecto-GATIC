# Validation Report

**Document:** _bmad-output/implementation-artifacts/1-5-livewire-3-instalado-e-integrado-en-el-layout.md
**Checklist:** _bmad/bmm/workflows/4-implementation/create-story/checklist.md
**Date:** 2025-12-28T234321Z

## Summary
- Total items: 152
- V PASS: 141
- ? PARTIAL: 0
- ? FAIL: 0
- ? N/A: 11

## Section Results

### **🚨 CRITICAL MISTAKES TO PREVENT:**
Counts: V 8 / ? 0 / ? 0 / ? 0 (Total 8)

[V PASS] (1) **Reinventing wheels** - Creating duplicate functionality instead of reusing existing
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (2) **Wrong libraries** - Using incorrect frameworks, versions, or dependencies
Evidence: Story L109: "### Librerías / herramientas (requisitos)"

[V PASS] (3) **Wrong file locations** - Violating project structure and organization
Evidence: Story L158: "### Project Structure Notes"

[V PASS] (4) **Breaking regressions** - Implementing changes that break existing functionality
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (5) **Ignoring UX** - Not following user experience design requirements
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (6) **Vague implementations** - Creating unclear, ambiguous implementations
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (7) **Lying about completion** - Implementing incorrectly or incompletely
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (8) **Not learning from past work** - Ignoring previous story learnings and patterns
Evidence: Story L140: "### Inteligencia de story previa (Story 1.4)"

### **When Running from Create-Story Workflow:**
Counts: V 5 / ? 0 / ? 0 / ? 0 (Total 5)

[V PASS] (9) The `{project_root}/_bmad/core/tasks/validate-workflow.xml` framework will automatically:
Evidence: Story L109: "### Librerías / herramientas (requisitos)"

[V PASS] (10) Load this checklist file
Evidence: Story L167: "### References"

[V PASS] (11) Load the newly created story file (`{story_file_path}`)
Evidence: Story L167: "### References"

[V PASS] (12) Load workflow variables from `{installed_path}/workflow.yaml`
Evidence: Story L167: "### References"

[V PASS] (13) Execute the validation process
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **When Running in Fresh Context:**
Counts: V 4 / ? 0 / ? 0 / ? 0 (Total 4)

[V PASS] (14) User should provide the story file path being reviewed
Evidence: Story L167: "### References"

[V PASS] (15) Load the story file directly
Evidence: Story L167: "### References"

[V PASS] (16) Load the corresponding workflow.yaml for variable context
Evidence: Story L167: "### References"

[V PASS] (17) Proceed with systematic analysis
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **Required Inputs:**
Counts: V 4 / ? 0 / ? 0 / ? 0 (Total 4)

[V PASS] (18) **Story file**: The story file to review and improve
Evidence: Story L167: "### References"

[V PASS] (19) **Workflow variables**: From workflow.yaml (story_dir, output_folder, epics_file, etc.)
Evidence: Story L167: "### References"

[V PASS] (20) **Source documents**: Epics, architecture, etc. (discovered or provided)
Evidence: Story L167: "### References"

[V PASS] (21) **Validation framework**: `validate-workflow.xml` (handles checklist execution)
Evidence: Story L109: "### Librerías / herramientas (requisitos)"

### **Step 1: Load and Understand the Target**
Counts: V 6 / ? 0 / ? 0 / ? 0 (Total 6)

[V PASS] (22) **Load the workflow configuration**: `{installed_path}/workflow.yaml` for variable inclusion
Evidence: Story L167: "### References"

[V PASS] (23) **Load the story file**: `{story_file_path}` (provided by user or discovered)
Evidence: Story L167: "### References"

[V PASS] (24) **Load validation framework**: `{project_root}/_bmad/core/tasks/validate-workflow.xml`
Evidence: Story L109: "### Librerías / herramientas (requisitos)"

[V PASS] (25) **Extract metadata**: epic_num, story_num, story_key, story_title from story file
Evidence: Story L167: "### References"

[V PASS] (26) **Resolve all workflow variables**: story_dir, output_folder, epics_file, architecture_file, etc.
Evidence: Story L167: "### References"

[V PASS] (27) **Understand current status**: What story implementation guidance is currently provided?
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **2.1 Epics and Stories Analysis**
Counts: V 7 / ? 0 / ? 0 / ? 0 (Total 7)

[V PASS] (28) Load `{epics_file}` (or sharded equivalents)
Evidence: Story L167: "### References"

[V PASS] (29) Extract **COMPLETE Epic {{epic_num}} context**:
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (30) Epic objectives and business value
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (31) ALL stories in this epic (for cross-story context)
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (32) Our specific story's requirements, acceptance criteria
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (33) Technical requirements and constraints
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (34) Cross-story dependencies and prerequisites
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **2.2 Architecture Deep-Dive**
Counts: V 6 / ? 0 / ? 0 / ? 5 (Total 11)

[V PASS] (35) Load `{architecture_file}` (single or sharded)
Evidence: Story L167: "### References"

[V PASS] (36) **Systematically scan for ANYTHING relevant to this story:**
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (37) Technical stack with versions (languages, frameworks, libraries)
Evidence: Story L109: "### Librerías / herramientas (requisitos)"

[V PASS] (38) Code structure and organization patterns
Evidence: Story L158: "### Project Structure Notes"

[? N/A] (39) API design patterns and contracts
Evidence: No aplica a esta historia (UI base/Bootstrap/Tailwind). Story L95: "### Alcance / fuera de alcance"

[? N/A] (40) Database schemas and relationships
Evidence: No aplica a esta historia (UI base/Bootstrap/Tailwind). Story L95: "### Alcance / fuera de alcance"

[V PASS] (41) Security requirements and patterns
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[? N/A] (42) Performance requirements and optimization strategies
Evidence: No aplica a esta historia (UI base/Bootstrap/Tailwind). Story L95: "### Alcance / fuera de alcance"

[V PASS] (43) Testing standards and frameworks
Evidence: Story L130: "### Testing (requisitos)"

[? N/A] (44) Deployment and environment patterns
Evidence: No aplica a esta historia (UI base/Bootstrap/Tailwind). Story L95: "### Alcance / fuera de alcance"

[? N/A] (45) Integration patterns and external services
Evidence: No aplica a esta historia (UI base/Bootstrap/Tailwind). Story L95: "### Alcance / fuera de alcance"

### **2.3 Previous Story Intelligence (if applicable)**
Counts: V 8 / ? 0 / ? 0 / ? 0 (Total 8)

[V PASS] (46) If `story_num > 1`, load the previous story file
Evidence: Story L140: "### Inteligencia de story previa (Story 1.4)"

[V PASS] (47) Extract **actionable intelligence**:
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (48) Dev notes and learnings
Evidence: Story L140: "### Inteligencia de story previa (Story 1.4)"

[V PASS] (49) Review feedback and corrections needed
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (50) Files created/modified and their patterns
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (51) Testing approaches that worked/didn't work
Evidence: Story L130: "### Testing (requisitos)"

[V PASS] (52) Problems encountered and solutions found
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (53) Code patterns and conventions established
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **2.4 Git History Analysis (if available)**
Counts: V 6 / ? 0 / ? 0 / ? 0 (Total 6)

[V PASS] (54) Analyze recent commits for patterns:
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (55) Files created/modified in previous work
Evidence: Story L140: "### Inteligencia de story previa (Story 1.4)"

[V PASS] (56) Code patterns and conventions used
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (57) Library dependencies added/changed
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (58) Architecture decisions implemented
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (59) Testing approaches used
Evidence: Story L130: "### Testing (requisitos)"

### **2.5 Latest Technical Research**
Counts: V 5 / ? 0 / ? 0 / ? 0 (Total 5)

[V PASS] (60) Identify any libraries/frameworks mentioned
Evidence: Story L109: "### Librerías / herramientas (requisitos)"

[V PASS] (61) Research latest versions and critical information:
Evidence: Story L109: "### Librerías / herramientas (requisitos)"

[V PASS] (62) Breaking changes or security updates
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (63) Performance improvements or deprecations
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (64) Best practices for current versions
Evidence: Story L109: "### Librerías / herramientas (requisitos)"

### **3.1 Reinvention Prevention Gaps**
Counts: V 3 / ? 0 / ? 0 / ? 0 (Total 3)

[V PASS] (65) **Wheel reinvention:** Areas where developer might create duplicate functionality
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (66) **Code reuse opportunities** not identified that could prevent redundant work
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (67) **Existing solutions** not mentioned that developer should extend instead of replace
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **3.2 Technical Specification DISASTERS**
Counts: V 3 / ? 0 / ? 0 / ? 2 (Total 5)

[V PASS] (68) **Wrong libraries/frameworks:** Missing version requirements that could cause compatibility issues
Evidence: Story L109: "### Librerías / herramientas (requisitos)"

[? N/A] (69) **API contract violations:** Missing endpoint specifications that could break integrations
Evidence: No aplica a esta historia (UI base/Bootstrap/Tailwind). Story L95: "### Alcance / fuera de alcance"

[? N/A] (70) **Database schema conflicts:** Missing requirements that could corrupt data
Evidence: No aplica a esta historia (UI base/Bootstrap/Tailwind). Story L95: "### Alcance / fuera de alcance"

[V PASS] (71) **Security vulnerabilities:** Missing security requirements that could expose the system
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (72) **Performance disasters:** Missing requirements that could cause system failures
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **3.3 File Structure DISASTERS**
Counts: V 2 / ? 0 / ? 0 / ? 2 (Total 4)

[V PASS] (73) **Wrong file locations:** Missing organization requirements that could break build processes
Evidence: Story L158: "### Project Structure Notes"

[V PASS] (74) **Coding standard violations:** Missing conventions that could create inconsistent codebase
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[? N/A] (75) **Integration pattern breaks:** Missing data flow requirements that could cause system failures
Evidence: No aplica a esta historia (UI base/Bootstrap/Tailwind). Story L95: "### Alcance / fuera de alcance"

[? N/A] (76) **Deployment failures:** Missing environment requirements that could prevent deployment
Evidence: No aplica a esta historia (UI base/Bootstrap/Tailwind). Story L95: "### Alcance / fuera de alcance"

### **3.4 Regression DISASTERS**
Counts: V 4 / ? 0 / ? 0 / ? 0 (Total 4)

[V PASS] (77) **Breaking changes:** Missing requirements that could break existing functionality
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (78) **Test failures:** Missing test requirements that could allow bugs to reach production
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (79) **UX violations:** Missing user experience requirements that could ruin the product
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (80) **Learning failures:** Missing previous story context that could repeat same mistakes
Evidence: Story L140: "### Inteligencia de story previa (Story 1.4)"

### **3.5 Implementation DISASTERS**
Counts: V 4 / ? 0 / ? 0 / ? 0 (Total 4)

[V PASS] (81) **Vague implementations:** Missing details that could lead to incorrect or incomplete work
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (82) **Completion lies:** Missing acceptance criteria that could allow fake implementations
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (83) **Scope creep:** Missing boundaries that could cause unnecessary work
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (84) **Quality failures:** Missing quality requirements that could deliver broken features
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **Step 4: LLM-Dev-Agent Optimization Analysis**
Counts: V 10 / ? 0 / ? 0 / ? 0 (Total 10)

[V PASS] (85) **Verbosity problems:** Excessive detail that wastes tokens without adding value
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (86) **Ambiguity issues:** Vague instructions that could lead to multiple interpretations
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (87) **Context overload:** Too much information not directly relevant to implementation
Evidence: Story L167: "### References"

[V PASS] (88) **Missing critical signals:** Key requirements buried in verbose text
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (89) **Poor structure:** Information not organized for efficient LLM processing
Evidence: Story L158: "### Project Structure Notes"

[V PASS] (90) **Clarity over verbosity:** Be precise and direct, eliminate fluff
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (91) **Actionable instructions:** Every sentence should guide implementation
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (92) **Scannable structure:** Use clear headings, bullet points, and emphasis
Evidence: Story L158: "### Project Structure Notes"

[V PASS] (93) **Token efficiency:** Pack maximum information into minimum text
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (94) **Unambiguous language:** Clear requirements with no room for interpretation
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **5.1 Critical Misses (Must Fix)**
Counts: V 3 / ? 0 / ? 0 / ? 1 (Total 4)

[V PASS] (95) Missing essential technical requirements
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (96) Missing previous story context that could cause errors
Evidence: Story L140: "### Inteligencia de story previa (Story 1.4)"

[V PASS] (97) Missing anti-pattern prevention that could lead to duplicate code
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[? N/A] (98) Missing security or performance requirements
Evidence: No aplica a esta historia (UI base/Bootstrap/Tailwind). Story L95: "### Alcance / fuera de alcance"

### **5.2 Enhancement Opportunities (Should Add)**
Counts: V 4 / ? 0 / ? 0 / ? 0 (Total 4)

[V PASS] (99) Additional architectural guidance that would help developer
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (100) More detailed technical specifications
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (101) Better code reuse opportunities
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (102) Enhanced testing guidance
Evidence: Story L130: "### Testing (requisitos)"

### **5.3 Optimization Suggestions (Nice to Have)**
Counts: V 3 / ? 0 / ? 0 / ? 0 (Total 3)

[V PASS] (103) Performance optimization hints
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (104) Additional context for complex scenarios
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (105) Enhanced debugging or development tips
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **5.4 LLM Optimization Improvements**
Counts: V 4 / ? 0 / ? 0 / ? 0 (Total 4)

[V PASS] (106) Token-efficient phrasing of existing content
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (107) Clearer structure for LLM processing
Evidence: Story L158: "### Project Structure Notes"

[V PASS] (108) More actionable and direct instructions
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (109) Reduced verbosity while maintaining completeness
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **Category 1: Critical Misses (Blockers)**
Counts: V 3 / ? 0 / ? 0 / ? 1 (Total 4)

[V PASS] (110) Essential technical requirements the developer needs but aren't provided
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (111) Previous story learnings that would prevent errors if ignored
Evidence: Story L140: "### Inteligencia de story previa (Story 1.4)"

[V PASS] (112) Anti-pattern prevention that would prevent code duplication
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[? N/A] (113) Security or performance requirements that must be followed
Evidence: No aplica a esta historia (UI base/Bootstrap/Tailwind). Story L95: "### Alcance / fuera de alcance"

### **Category 2: Enhancement Opportunities**
Counts: V 4 / ? 0 / ? 0 / ? 0 (Total 4)

[V PASS] (114) Architecture guidance that would significantly help implementation
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (115) Technical specifications that would prevent wrong approaches
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (116) Code reuse opportunities the developer should know about
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (117) Testing guidance that would improve quality
Evidence: Story L130: "### Testing (requisitos)"

### **Category 3: Optimization Insights**
Counts: V 3 / ? 0 / ? 0 / ? 0 (Total 3)

[V PASS] (118) Performance or efficiency improvements
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (119) Development workflow optimizations
Evidence: Story L167: "### References"

[V PASS] (120) Additional context for complex scenarios
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **🤖 LLM OPTIMIZATION (Token Efficiency & Clarity)**
Counts: V 4 / ? 0 / ? 0 / ? 0 (Total 4)

[V PASS] (121) Reduce verbosity while maintaining completeness
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (122) Improve structure for better LLM processing
Evidence: Story L158: "### Project Structure Notes"

[V PASS] (123) Make instructions more actionable and direct
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (124) Enhance clarity and reduce ambiguity}}
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **Step 6: Interactive User Selection**
Counts: V 5 / ? 0 / ? 0 / ? 0 (Total 5)

[V PASS] (125) **all** - Apply all suggested improvements
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (126) **critical** - Apply only critical issues
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (127) **select** - I'll choose specific numbers
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (128) **none** - Keep story as-is
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (129) **details** - Show me more details about any suggestion
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **Step 7: Apply Selected Improvements**
Counts: V 4 / ? 0 / ? 0 / ? 0 (Total 4)

[V PASS] (130) **Load the story file**
Evidence: Story L167: "### References"

[V PASS] (131) **Apply accepted changes** (make them look natural, as if they were always there)
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (132) **DO NOT reference** the review process, original LLM, or that changes were "added" or "enhanced"
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (133) **Ensure clean, coherent final story** that reads as if it was created perfectly the first time
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **Step 8: Confirmation**
Counts: V 2 / ? 0 / ? 0 / ? 0 (Total 2)

[V PASS] (134) Review the updated story
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (135) Run `dev-story` for implementation
Evidence: Story L90: "### Guardrails técnicos (MUST)"

### **💪 COMPETITIVE EXCELLENCE MINDSET**
Counts: V 17 / ? 0 / ? 0 / ? 0 (Total 17)

[V PASS] (136) ✅ Clear technical requirements they must follow
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (137) ✅ Previous work context they can build upon
Evidence: Story L140: "### Inteligencia de story previa (Story 1.4)"

[V PASS] (138) ✅ Anti-pattern prevention to avoid common mistakes
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (139) ✅ Comprehensive guidance for efficient implementation
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (140) ✅ **Optimized content structure** for maximum clarity and minimum token waste
Evidence: Story L158: "### Project Structure Notes"

[V PASS] (141) ✅ **Actionable instructions** with no ambiguity or verbosity
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (142) ✅ **Efficient information density** - maximum guidance in minimum text
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (143) Reinvent existing solutions
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (144) Use wrong approaches or libraries
Evidence: Story L109: "### Librerías / herramientas (requisitos)"

[V PASS] (145) Create duplicate functionality
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (146) Miss critical requirements
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (147) Make implementation errors
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (148) Misinterpret requirements due to ambiguity
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (149) Waste tokens on verbose, non-actionable content
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (150) Struggle to find critical information buried in text
Evidence: Story L90: "### Guardrails técnicos (MUST)"

[V PASS] (151) Get confused by poor structure or organization
Evidence: Story L158: "### Project Structure Notes"

[V PASS] (152) Miss key implementation signals due to inefficient communication
Evidence: Story L90: "### Guardrails técnicos (MUST)"

## Failed Items

Ninguno.

## Partial Items

Ninguno.

## Recommendations
1. Must Fix: Ninguno (no FAIL detectados).
2. Should Improve: Si se decide upgrade de Bootstrap (5.3.x), definirlo explícitamente y validar contraste/branding.
3. Consider: Convertir `/` a redirect a login/dashboard para reducir superficie pública y evitar templates no deseados.
