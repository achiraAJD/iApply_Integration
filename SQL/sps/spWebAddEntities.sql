USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebAddEntities]    Script Date: 1/07/2022 11:48:37 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/*-------------------------------------------------------------------------------------------
| OBJECT: 		spWebAddEntities
| PURPOSE:  	Add to Entities table
+--------------------------------------------------------------------------------------------
| HISTORY:
| DATE			WHO      		DESCRIPTION OF CHANGE
| ----------- 	--------------- -------------------------------------------------------
| 21-Feb-2017  	Dave Dennis		Original
| 13-Nov-2019  	Brian Nikoloff	Add Position of authority
| 15-Jun-2020	David White		Add Gaming Machine Licensee
+--------------------------------------------------------------------------------------------*/

ALTER   PROCEDURE [dbo].[spWebAddEntities]

@LastUpdateUser				varchar(50),
@ENT_ET_ID					numeric (18,0),
@ED_Name1					varchar(100),
@ED_Name2					varchar (50),
@ED_Name3					varchar (50),
@ED_Surname					varchar (50),
@ENT_Gender					char(1),
@ENT_DOB					smalldatetime,
@ENT_PreferredName			varchar(30),
@ENT_Notes					varchar (1000) = NULL,
@ED_ABN						varchar(11) = NULL,
@ENT_GT1					bit = 0,
@ENT_GTDate1				datetime = NULL,
@ENT_Address1				varchar(50), 
@ENT_Address2				varchar(50),
@ENT_PostCode				varchar(4),
@ENT_State					varchar(3),
@ENT_Town					varchar(30),
@ENT_Phone					varchar(15),
@ENT_Fax					varchar(15) = NULL,
@ENT_Mobile					varchar(15),
@ENT_Email					varchar(255),
@ENT_CasinoNumber			numeric(18,0) = NULL,
@ENT_OPS					bit = 0,
@ENT_OPSDate				datetime = NULL,
@ENT_RES					bit = 0,
@ENT_RESDate				datetime = NULL,
@ENT_PostalAddress1			varchar(50), 
@ENT_PostalAddress2			varchar(50),
@ENT_PostalPostCode			varchar(4),
@ENT_PostalState			varchar(3),
@ENT_PostalTown				varchar(30),
@ENT_OCBA					numeric(18,0) = NULL,
@ENT_Web                    varchar(255) = NULL, --achira
@ED_ACN						varchar(11) = NULL, --achira
@ED_TrusteeName             varchar(100) = NULL, --achira

--if we pass in an ENT_ID, don't add the entity
@ENT_ID						numeric(18,0) = NULL,

@Categories					varchar(500) = NULL, --achira
@AS_ID						numeric(18,0) = NULL, --achira
@APP_ID						numeric(18,0),

@PIDType					varchar(20),
@AP_DateLodged				datetime,  
@AP_CriminalHistory			numeric(18,0),

@ENT_ID_OUT					NUMERIC(18,0) = NULL OUTPUT

AS

BEGIN

   DECLARE @ED_ID numeric(18,0)
   SET xact_abort ON

   BEGIN
      BEGIN TRANSACTION
	  
	  PRINT 'checkpoint 1'

	  --Clean mobile number
	  SET @ENT_Mobile = (SELECT dbo.udfCleanString(@ENT_Mobile,'%[^0-9]%'))
	  IF @ENT_ID IS NULL
	  BEGIN
		  INSERT Entities (
			ENT_ET_ID,ENT_Gender,ENT_DOB,ENT_PreferredName,
			ENT_Notes, ENT_GT1,ENT_GTDate1, 
			ENT_OPS, ENT_OPSDate, ENT_RES, ENT_RESDate,
			ENT_Address1,ENT_Address2,ENT_PostCode,ENT_State,ENT_Town,
			ENT_Phone,ENT_Fax,ENT_Mobile,ENT_Email,ENT_CasinoNumber, ENT_OCBA,
			ENT_PostalAddress1,ENT_PostalAddress2,ENT_PostalPostCode,ENT_PostalState,ENT_PostalTown,
			LastUpdateDateTime,
			LastUpdateUser,CreationDateTime,CreationUser,ENT_Web
		  )
		  VALUES (
			@ENT_ET_ID,@ENT_Gender,@ENT_DOB,@ENT_PreferredName,
			@ENT_Notes, @ENT_GT1, @ENT_GTDate1, 
			@ENT_OPS, @ENT_OPSDate, @ENT_RES, @ENT_RESDate,
			@ENT_Address1,@ENT_Address2,@ENT_PostCode,@ENT_State,@ENT_Town,
			@ENT_Phone,@ENT_Fax,@ENT_Mobile,@ENT_Email,@ENT_CasinoNumber, @ENT_OCBA,
			@ENT_PostalAddress1,@ENT_PostalAddress2,@ENT_PostalPostCode,@ENT_PostalState,@ENT_PostalTown,
			GETDATE(),
			@LastUpdateUser,GETDATE(),@LastUpdateUser, @ENT_Web
		  )
		
		  -- return identity column to caller
		  SELECT @ENT_ID = @@IDENTITY
		
		  INSERT EntityDetails (ED_ABN,
			ED_ENT_ID, ED_Name1,ED_Name2,ED_Name3,ED_Surname,
			LastUpdateDateTime,
			LastUpdateUser,CreationDateTime,CreationUser,ED_ACN,ED_TrusteeName)
		  VALUES (@ED_ABN,	
			@ENT_ID, @ED_Name1,@ED_Name2,@ED_Name3,@ED_Surname,		
			GETDATE(),
			@LastUpdateUser,GETDATE(),@LastUpdateUser,@ED_ACN,@ED_TrusteeName)
		
		  -- return identity column of the last insert
		  SELECT @ED_ID = @@IDENTITY
		
		  UPDATE Entities SET 
			ENT_ED_ID =	@ED_ID,
			LastUpdateDateTime = GETDATE(),
			LastUpdateUser = @LastUpdateUser
		  WHERE ENT_ID = @ENT_ID

		  EXEC spCreateEntityClientID @ENT_ID  -- Add a client ID to the entity
		  
		  IF @Categories IS NULL --achira
		  BEGIN
			DECLARE @LV_ID numeric (18,0)
			DECLARE @NewIDNo numeric (18,0)

			--Get the next ID Number                
			SELECT @LV_ID = LookupValues.LV_ID, @NewIDNo = LookupValues.LV_Value FROM Lookups INNER JOIN LookupValues ON Lookups.LOO_ID = LookupValues.LV_LOO_ID WHERE Lookups.LOO_Code='IDNumber' AND LookupValues.LV_Desc='Application'                
			-- Update the values table                 
			 UPDATE LookupValues SET LookupValues.LV_Value = @NewIDNo + 1 WHERE LookupValues.LV_ID = @LV_ID                
			-- Add the new entity codes record                
			INSERT INTO EntityCodes (EC_Code, EC_ENT_ID, EC_Active, EC_AS_ID, CreationDateTime, CreationUser, LastUpdateDateTime, LastUpdateUser)                
			VALUES (@NewIDNo, @ENT_ID, 1, @AS_ID, GETDATE(), @LastUpdateUser, GETDATE(), @LastUpdateUser)			
		  END -- achira
	  END

	  IF @Categories IS NOT NULL --achira
	  BEGIN --achira
		  --insert Application People record and create EC_Code
		  DECLARE @AP_ID NUMERIC(18,0) 
		  EXEC [spWebPIDUpdate] @LastUpdateUser, @APP_ID, @ENT_ID, @AP_DateLodged, @PIDType, @AP_CriminalHistory, @AP_ID = @AP_ID OUTPUT

	 
		  --add approval categories records
		  DECLARE @pos INT
		  DECLARE @Category VARCHAR(255)
		  DECLARE @AC_ID numeric(18,0)
		  DECLARE @APC_ID numeric(18,0)

		  --if multiple categoried are left/entered do this
		  WHILE CHARINDEX(',', @Categories) > 0
		  BEGIN
				SET @AC_ID = 0
				SELECT @pos  = CHARINDEX(',', @Categories)  
				SELECT @Category = SUBSTRING(@Categories, 1, @pos-1)
				SELECT @Categories = SUBSTRING(@Categories, @pos+1, LEN(@Categories)-@pos)

				IF @Category = 'Responsible person' OR @Category = 'SM'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'SM' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Licensee' OR @Category = 'LL'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'LL' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Director' OR @Category = 'DL'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'DL' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Shareholder' OR @Category = 'EI'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'EI' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Committee member' OR @Category = 'LC'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'LC' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Sensitive person' OR @Category = 'SP'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'SP' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Position of responsibility' OR @Category = 'PR'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'PR' AND  AC_Active = 1
				END 
				-- PA	Position of Authority
				ELSE IF @Category = 'Position of authority' OR @Category = 'PA'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'PA' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Gaming Technician' OR @Category = 'GT'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'GT' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Gaming Machine Licensee' OR @Category = 'GL'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'GL' AND  AC_Active = 1
				END
				ELSE IF @Category = 'Committee Member Gaming' OR @Category = 'CM'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'CM' AND  AC_Active = 1
				END


				IF @AC_ID <> 0
				BEGIN
					INSERT INTO ApplicationPeopleCats (
						 APC_AC_ID
						,APC_AP_ID
						,APC_Status
						,CreationDateTime
						,CreationUser
						,LastUpdateDateTime
						,LastUpdateUser)
					VALUES (
						@AC_ID,
						@AP_ID,
						'A',
						GETDATE(),
						@LastUpdateUser,
						GETDATE(),
						@LastUpdateUser)

					SET @APC_ID = @@IDENTITY

					INSERT INTO ApplicationPeopleCatHistory (
						 [APH_APC_ID]
						,[APH_Date]
						,[APH_Status]
						,[CreationDateTime]
						,[CreationUser]
						,[LastUpdateDateTime]
						,[LastUpdateUser])
					VALUES (
						@APC_ID,
						GETDATE(),
						'A',
						GETDATE(),
						@LastUpdateUser,
						GETDATE(),
						@LastUpdateUser)
				END
		  END

		  --if only one category is left/entered do this
		  IF @Categories is not NULL and @Categories <> ''
		  BEGIN
				SET @AC_ID = 0
				SET @Category = @Categories
				IF @Category = 'Responsible person' OR @Category = 'SM'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'SM' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Licensee' OR @Category = 'LL'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'LL' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Director' OR @Category = 'DL'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'DL' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Shareholder' OR @Category = 'EI'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'EI' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Committee member' OR @Category = 'LC'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'LC' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Sensitive person' OR @Category = 'SP'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'SP' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Position of responsibility' OR @Category = 'PR'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'PR' AND  AC_Active = 1
				END 
				-- PA	Position of Authority
				ELSE IF @Category = 'Position of authority' OR @Category = 'PA'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'PA' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Gaming Technician' OR @Category = 'GT'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'GT' AND  AC_Active = 1
				END 
				ELSE IF @Category = 'Gaming Machine Licensee' OR @Category = 'GL'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'GL' AND  AC_Active = 1
				END
				ELSE IF @Category = 'Committee Member Gaming' OR @Category = 'CM'
				BEGIN
					SELECT @AC_ID = AC_ID FROM ApprovalCategories WHERE AC_Prefix = 'CM' AND  AC_Active = 1
				END

				IF @AC_ID <> 0
				BEGIN
					INSERT INTO ApplicationPeopleCats (
						 APC_AC_ID
						,APC_AP_ID
						,APC_Status
						,CreationDateTime
						,CreationUser
						,LastUpdateDateTime
						,LastUpdateUser)
					VALUES (
						@AC_ID,
						@AP_ID,
						'A',
						GETDATE(),
						@LastUpdateUser,
						GETDATE(),
						@LastUpdateUser)

					SET @APC_ID = @@IDENTITY

					INSERT INTO ApplicationPeopleCatHistory (
						 [APH_APC_ID]
						,[APH_Date]
						,[APH_Status]
						,[CreationDateTime]
						,[CreationUser]
						,[LastUpdateDateTime]
						,[LastUpdateUser])
					VALUES (
						@APC_ID,
						GETDATE(),
						'A',
						GETDATE(),
						@LastUpdateUser,
						GETDATE(),
						@LastUpdateUser)
				END
			END

		  --add cards to generate record
		  EXEC spCardsToGenerate @LastUpdateUser, @AP_ID
	END -- achira
      COMMIT TRANSACTION
   END
   -- For Portal
   SET @ENT_ID_OUT = @ENT_ID
   -- flag success
   SELECT @ENT_ID as ENT_ID  
END;

GRANT EXECUTE on [spWebAddEntities] to WebUser;
GRANT EXECUTE on [spWebAddEntities] to LGOPortal;
