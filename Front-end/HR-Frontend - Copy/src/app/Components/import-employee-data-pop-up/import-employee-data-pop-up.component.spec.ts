import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ImportEmployeeDataPopUpComponent } from './import-employee-data-pop-up.component';

describe('ImportEmployeeDataPopUpComponent', () => {
  let component: ImportEmployeeDataPopUpComponent;
  let fixture: ComponentFixture<ImportEmployeeDataPopUpComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ImportEmployeeDataPopUpComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ImportEmployeeDataPopUpComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
